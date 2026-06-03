<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class DriverPersistenceService
{
    private const DOCUMENT_FIELDS = [
        'driver_license_document',
        'driver_phd_license_document',
        'phd_card_document',
        'dvla_license_summary',
        'misc_document',
        'proof_of_address_document',
    ];

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
            'driver_license_document' => 'nullable|file',
            'driver_phd_license_document' => 'nullable|file',
            'phd_card_document' => 'nullable|file',
            'dvla_license_summary' => 'nullable|file',
            'misc_document' => 'nullable|file',
            'proof_of_address_document' => 'nullable|file',
        ];
    }

    public function createFromRequest(Request $request, Tenant $tenant): Driver
    {
        $validated = $request->validate($this->validationRules());
        $validated = $this->attributesFromValidated($request, $validated);

        return $this->createFromValidatedAttributes($validated, $tenant);
    }

    public function updateFromRequest(Driver $driver, Request $request, Tenant $tenant): Driver
    {
        $validated = $request->validate($this->validationRules($driver));
        $validated = $this->attributesFromValidated($request, $validated, $driver);
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
        $driverAttributes = Arr::only($validated, array_keys($this->validationRules($existing)));

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
            if (! $request->hasFile($field)) {
                continue;
            }

            $storedName = $this->storeDocument($request->file($field));

            if ($storedName === null) {
                continue;
            }

            if ($existing?->{$field}) {
                $this->deleteDocument($existing->{$field});
            }

            $validated[$field] = $storedName;
        }

        return $validated;
    }

    private function storeDocument($file): ?string
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            $dims = getimagesize($file);
            $name = time().'-'.$dims[0].'-'.$dims[1].'.'.$file->extension();
        } else {
            $name = time().'-'.uniqid().'.'.$file->extension();
        }

        $path = public_path('uploads/driver_licenses/');

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return $file->move($path, $name) ? $name : null;
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
