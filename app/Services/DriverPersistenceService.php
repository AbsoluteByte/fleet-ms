<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverDocumentArchive;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

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
    public function attributesFromValidated(Request $request, array $validated, ?Driver $existing = null): array
    {
        $attributeKeys = array_diff(
            array_keys($this->validationRules($existing)),
            self::DOCUMENT_FIELDS
        );

        $driverAttributes = Arr::only($validated, $attributeKeys);

        if (! filled($driverAttributes['county'] ?? null)) {
            $driverAttributes['county'] = null;
        }

        if (! filled($driverAttributes['address2'] ?? null)) {
            $driverAttributes['address2'] = null;
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
