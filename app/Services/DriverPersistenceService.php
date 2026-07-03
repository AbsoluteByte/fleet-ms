<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverDocumentArchive;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class DriverPersistenceService
{
    public const DOCUMENT_FIELDS = [
        'driver_license_document',
        'driver_phd_license_document',
        'phd_card_document',
        'dvla_license_summary',
        'misc_document',
        'proof_of_address_document',
    ];

    public const DOCUMENT_FIELD_LABELS = [
        'driver_license_document' => 'Driver License Document',
        'driver_phd_license_document' => 'PHD License Document',
        'phd_card_document' => 'PHD Card Document',
        'dvla_license_summary' => 'DVLA License Summary',
        'misc_document' => 'Miscellaneous Document',
        'proof_of_address_document' => 'Proof of Address',
    ];

    /**
     * Scalar profile fields required before creating an agreement.
     *
     * @return array<string, string>
     */
    public static function requiredProfileFieldLabels(): array
    {
        return [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'dob' => 'Date of Birth',
            'email' => 'Email',
            'phone_number' => 'Phone Number',
            'address1' => 'Address Line 1',
            'post_code' => 'Post Code',
            'town' => 'Town',
            'country_id' => 'Country',
            'driver_license_number' => 'Driver License Number',
            'driver_license_expiry_date' => 'Driver License Expiry Date',
            'next_of_kin' => 'Next of Kin',
            'next_of_kin_phone' => 'Next of Kin Phone',
        ];
    }

    public function isProfileCompleteForAgreement(Driver $driver): bool
    {
        return $this->missingProfileFieldLabels($driver) === [];
    }

    /**
     * @return list<string>
     */
    public function missingProfileFieldLabels(Driver $driver): array
    {
        $missing = [];

        foreach (self::requiredProfileFieldLabels() as $field => $label) {
            $value = $driver->{$field};

            if (! filled($value)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /**
     * @return list<string>
     */
    public static function documentFields(): array
    {
        return self::DOCUMENT_FIELDS;
    }

    public function removeDocument(Driver $driver, string $field): void
    {
        if (! in_array($field, self::DOCUMENT_FIELDS, true)) {
            abort(404);
        }

        $filename = $driver->{$field};

        if ($filename) {
            $this->archiveDocument($driver, $field, $filename, 'removed');
            $driver->update([
                $field => null,
                'updatedBy' => Auth::id(),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function validationRules(?Driver $existing = null): array
    {
        $driverId = $existing?->id;

        return [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'email' => 'required|email|unique:drivers,email,'.($driverId ?? 'NULL'),
            'phone_number' => 'required|string|max:20',
            'ni_number' => 'nullable|string|max:20',
            'address1' => 'required|string|max:255',
            'address2' => 'nullable|string|max:255',
            'post_code' => 'required|string|max:20',
            'town' => 'required|string|max:100',
            'county' => 'nullable|string|max:100',
            'country_id' => 'required|numeric|exists:countries,id',
            'driver_license_number' => 'required|string|unique:drivers,driver_license_number,'.($driverId ?? 'NULL'),
            'driver_license_expiry_date' => 'required|date',
            'phd_license_number' => 'nullable|string',
            'phd_license_expiry_date' => 'nullable|date',
            'next_of_kin' => 'required|string|max:255',
            'next_of_kin_phone' => 'required|string|max:20',
            ...$this->documentValidationRules(),
        ];
    }

    /**
     * Relaxed driver rules for new-driver flow on add reservation only.
     *
     * @return array<string, mixed>
     */
    public function reservationMinimalValidationRules(?Driver $existing = null): array
    {
        $driverId = $existing?->id;

        return [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'email' => [
                'nullable',
                'email',
                Rule::unique('drivers', 'email')->ignore($driverId),
            ],
            'phone_number' => 'nullable|string|max:20',
            'ni_number' => 'nullable|string|max:20',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:20',
            'town' => 'nullable|string|max:100',
            'county' => 'nullable|string|max:100',
            'country_id' => 'nullable|numeric|exists:countries,id',
            'driver_license_number' => [
                'nullable',
                'string',
                Rule::unique('drivers', 'driver_license_number')->ignore($driverId),
            ],
            'driver_license_expiry_date' => 'nullable|date',
            'phd_license_number' => 'nullable|string',
            'phd_license_expiry_date' => 'nullable|date',
            'next_of_kin' => 'nullable|string|max:255',
            'next_of_kin_phone' => 'nullable|string|max:20',
            ...$this->documentValidationRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function documentValidationRules(): array
    {
        return [
            'driver_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'driver_phd_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'phd_card_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'dvla_license_summary' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'misc_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'proof_of_address_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }

    public function createFromRequest(Request $request, Tenant $tenant): Driver
    {
        $validated = $request->validate($this->validationRules());
        $validated = $this->attributesFromValidated($request, $validated);
        $validated['is_active'] = $request->has('is_active');

        return $this->createFromValidatedAttributes($validated, $tenant);
    }

    public function updateFromRequest(Driver $driver, Request $request, Tenant $tenant): Driver
    {
        $validated = $request->validate($this->validationRules($driver));
        $validated = $this->attributesFromValidated($request, $validated, $driver);
        $validated['is_active'] = $request->has('is_active');
        $validated['tenant_id'] = $tenant->id;
        $validated['updatedBy'] = Auth::id();
        $driver->update($validated);

        return $driver->fresh();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function attributesFromValidated(
        Request $request,
        array $validated,
        ?Driver $existing = null,
        bool $minimal = false
    ): array {
        $rules = $minimal
            ? $this->reservationMinimalValidationRules($existing)
            : $this->validationRules($existing);

        $attributeKeys = array_diff(
            array_keys($rules),
            self::DOCUMENT_FIELDS
        );

        $driverAttributes = Arr::only($validated, $attributeKeys);

        foreach (['county', 'address2', 'email', 'driver_license_number', 'country_id'] as $nullableField) {
            if (! filled($driverAttributes[$nullableField] ?? null)) {
                $driverAttributes[$nullableField] = null;
            }
        }

        if ($minimal) {
            foreach (array_keys($driverAttributes) as $field) {
                if ($field === 'first_name') {
                    continue;
                }
                if (! filled($driverAttributes[$field] ?? null)) {
                    $driverAttributes[$field] = null;
                }
            }
        }

        return $this->mergeUploadedDocuments($request, $driverAttributes, $existing);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function createFromValidatedAttributes(array $validated, Tenant $tenant): Driver
    {
        $validated['tenant_id'] = $tenant->id;
        $validated['createdBy'] = Auth::id();

        return Driver::create($validated);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function updateFromValidatedAttributes(Driver $driver, array $validated, Tenant $tenant): Driver
    {
        $validated['tenant_id'] = $tenant->id;
        $validated['updatedBy'] = Auth::id();
        $driver->update($validated);

        return $driver->fresh();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function mergeUploadedDocuments(Request $request, array $validated, ?Driver $existing = null): array
    {
        foreach (self::DOCUMENT_FIELDS as $field) {
            $file = $request->file($field);

            if (! $file || ! $file->isValid()) {
                continue;
            }

            $storedName = $this->storeDocument($file);

            if ($storedName === null) {
                continue;
            }

            if ($existing?->exists) {
                $previousFilename = Driver::query()
                    ->whereKey($existing->id)
                    ->value($field);

                if (filled($previousFilename) && $previousFilename !== $storedName) {
                    $this->archiveDocument($existing, $field, (string) $previousFilename, 'replaced');
                }
            }

            $validated[$field] = $storedName;
        }

        return $validated;
    }

    private function storeDocument($file): ?string
    {
        $mimeType = $file->getMimeType() ?? '';
        $extension = $file->getClientOriginalExtension() ?: $file->extension();

        if (str_starts_with($mimeType, 'image/')) {
            $dims = @getimagesize($file->getRealPath());
            $name = $dims
                ? time().'-'.$dims[0].'-'.$dims[1].'.'.$extension
                : time().'-'.uniqid().'.'.$extension;
        } else {
            $name = time().'-'.uniqid().'.'.$extension;
        }

        $path = public_path('uploads/driver_licenses/');

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return $file->move($path, $name) ? $name : null;
    }

    private function archiveDocument(Driver $driver, string $field, string $filename, string $reason): void
    {
        if (! in_array($field, self::DOCUMENT_FIELDS, true)) {
            abort(404);
        }

        DriverDocumentArchive::create([
            'driver_id' => $driver->id,
            'document_field' => $field,
            'filename' => $filename,
            'document_label' => self::DOCUMENT_FIELD_LABELS[$field] ?? $field,
            'reason' => $reason,
            'archived_by' => Auth::id(),
            'archived_at' => now(),
        ]);
    }

    private function deleteDocument(?string $filename): void
    {
        if (! $filename) {
            return;
        }

        $path = public_path('uploads/driver_licenses/'.$filename);

        if (File::exists($path)) {
            File::delete($path);
        }
    }
}
