<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarInsurance;
use App\Models\InsuranceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CarInsuranceBulkImportService
{
    public function __construct(
        private readonly InsuranceCertificateParser $parser,
        private readonly InsuranceStatusResolver $statusResolver,
        private readonly CarInsuranceAutoCancelService $autoCancelService,
    ) {}

    /**
     * @param  list<string>  $pdfPaths  Absolute paths to PDF files
     * @return array{summary: array<string, int>, rows: list<array<string, mixed>>}
     */
    public function import(
        array $pdfPaths,
        int $tenantId,
        int $insuranceProviderId,
        int $notifyBeforeExpiry,
    ): array {
        @set_time_limit(600);

        InsuranceProvider::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($insuranceProviderId)
            ->firstOrFail();

        $activeStatusId = $this->statusResolver->activeStatusId();
        $carsByRegistration = $this->loadCarsByRegistration($tenantId);
        $seenRegistrations = [];
        $rows = [];

        foreach ($pdfPaths as $pdfPath) {
            $rows[] = $this->processFile(
                $pdfPath,
                $tenantId,
                $insuranceProviderId,
                $notifyBeforeExpiry,
                $activeStatusId,
                $carsByRegistration,
                $seenRegistrations
            );
        }

        $summary = [
            'total' => count($rows),
            'success' => count(array_filter($rows, fn ($r) => $r['status'] === 'success')),
            'skipped' => count(array_filter($rows, fn ($r) => $r['status'] === 'skipped')),
            'failed' => count(array_filter($rows, fn ($r) => $r['status'] === 'failed')),
        ];

        return [
            'summary' => $summary,
            'rows' => $rows,
            'imported_at' => now()->toDateTimeString(),
            'insurance_provider_id' => $insuranceProviderId,
        ];
    }

    /**
     * @param  array<string, Car>  $carsByRegistration
     * @param  array<string, true>  $seenRegistrations
     * @return array<string, mixed>
     */
    private function processFile(
        string $pdfPath,
        int $tenantId,
        int $insuranceProviderId,
        int $notifyBeforeExpiry,
        int $activeStatusId,
        array $carsByRegistration,
        array &$seenRegistrations,
    ): array {
        $filename = basename($pdfPath);
        $base = [
            'filename' => $filename,
            'registration' => null,
            'car_id' => null,
            'car_edit_url' => null,
            'start_date' => null,
            'expiry_date' => null,
            'replaced_expired' => false,
        ];

        if (! is_file($pdfPath) || ! str_ends_with(strtolower($pdfPath), '.pdf')) {
            return array_merge($base, [
                'status' => 'failed',
                'message' => 'Invalid or missing PDF file.',
            ]);
        }

        $parsed = $this->parser->parse($pdfPath);

        if (! $parsed) {
            return array_merge($base, [
                'status' => 'failed',
                'message' => 'Could not parse registration or dates from filename/PDF.',
            ]);
        }

        $registration = $parsed->registration;
        $base['registration'] = $registration;
        $base['start_date'] = $parsed->startDate->format('Y-m-d');
        $base['expiry_date'] = $parsed->expiryDate->format('Y-m-d');

        if (isset($seenRegistrations[$registration])) {
            return array_merge($base, [
                'status' => 'skipped',
                'message' => 'Duplicate registration in this upload batch.',
            ]);
        }

        $car = $carsByRegistration[$registration] ?? null;

        if (! $car) {
            return array_merge($base, [
                'status' => 'skipped',
                'message' => "Car not found for registration {$registration}.",
            ]);
        }

        $base['car_id'] = $car->id;
        $base['car_edit_url'] = route('cars.edit', $car);

        try {
            return DB::transaction(function () use (
                $pdfPath,
                $filename,
                $base,
                $parsed,
                $tenantId,
                $insuranceProviderId,
                $notifyBeforeExpiry,
                $activeStatusId,
                $car,
                &$seenRegistrations,
                $registration
            ) {
                $latestInsurance = CarInsurance::query()
                    ->where('car_id', $car->id)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->with('status')
                    ->first();

                $replacedExpired = false;

                if ($latestInsurance
                    && (int) $latestInsurance->status_id === $activeStatusId
                    && $latestInsurance->expiry_date
                ) {
                    if ($latestInsurance->expiry_date->copy()->startOfDay()->gte(now()->startOfDay())) {
                        return array_merge($base, [
                            'status' => 'skipped',
                            'message' => 'Already has active insurance (not expired). New insurance was not added.',
                        ]);
                    }

                    $this->autoCancelService->cancelExpiredActiveForCar($car->id);
                    $replacedExpired = true;
                }

                $storedDocument = $this->storeInsuranceDocument($pdfPath);
                $latestStatusName = strtolower(trim((string) optional($latestInsurance?->status)->name));
                $isLatestClosedCycle = in_array($latestStatusName, ['cancelled', 'canceled'], true);
                $startingNewCycle = $latestInsurance && $isLatestClosedCycle;

                $insuranceData = [
                    'tenant_id' => $tenantId,
                    'insurance_provider_id' => $insuranceProviderId,
                    'start_date' => $parsed->startDate->format('Y-m-d'),
                    'expiry_date' => $parsed->expiryDate->format('Y-m-d'),
                    'applied_date' => $latestInsurance?->applied_date?->format('Y-m-d'),
                    'canceled_date' => null,
                    'notify_before_expiry' => $notifyBeforeExpiry,
                    'status_id' => $activeStatusId,
                    'insurance_document' => $storedDocument,
                ];

                if ($replacedExpired || ! $latestInsurance || $startingNewCycle) {
                    CarInsurance::create(array_merge($insuranceData, ['car_id' => $car->id]));
                } else {
                    $latestInsurance->update($insuranceData);
                }

                $seenRegistrations[$registration] = true;

                $message = $replacedExpired
                    ? 'Insurance added (previous expired active policy was cancelled).'
                    : 'Insurance added successfully.';

                return array_merge($base, [
                    'status' => 'success',
                    'message' => $message,
                    'replaced_expired' => $replacedExpired,
                ]);
            });
        } catch (\Throwable $e) {
            return array_merge($base, [
                'status' => 'failed',
                'message' => 'Error saving insurance: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, Car>
     */
    private function loadCarsByRegistration(int $tenantId): array
    {
        $map = [];

        Car::query()
            ->where('tenant_id', $tenantId)
            ->select(['id', 'registration', 'tenant_id'])
            ->chunkById(500, function ($cars) use (&$map) {
                foreach ($cars as $car) {
                    $key = $this->parser->normalizeRegistration($car->registration);
                    $map[$key] = $car;
                }
            });

        return $map;
    }

    private function storeInsuranceDocument(string $sourcePath): string
    {
        $directory = public_path('uploads/cars/insurance_documents');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $name = time().'-'.Str::uuid().'.pdf';
        $destination = $directory.DIRECTORY_SEPARATOR.$name;

        if (! copy($sourcePath, $destination)) {
            throw new \RuntimeException('Failed to copy insurance document to storage.');
        }

        return $name;
    }
}
