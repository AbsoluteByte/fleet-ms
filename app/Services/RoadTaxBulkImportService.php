<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarRoadTax;
use Illuminate\Support\Facades\DB;

class RoadTaxBulkImportService
{
    public function __construct(
        private readonly RoadTaxSlipExtractionService $extractionService,
        private readonly CarRoadTaxSornSyncService $sornSyncService,
    ) {}

    /**
     * @param  list<array{
     *     row_id: string,
     *     filename: string,
     *     registration: string,
     *     start_date: string,
     *     term: string,
     *     amount: float|string,
     * }>  $rows
     * @return array{
     *     imported_at: string,
     *     summary: array{total: int, success: int, skipped: int, failed: int},
     *     rows: list<array<string, mixed>>
     * }
     */
    public function apply(int $tenantId, array $rows): array
    {
        $carsByRegistration = $this->loadCarsByRegistration($tenantId);
        $seenCarStarts = [];
        $reportRows = [];
        $carsToSyncSorn = [];

        foreach ($rows as $row) {
            $base = [
                'row_id' => $row['row_id'],
                'filename' => $row['filename'],
                'registration' => $row['registration'],
                'start_date' => $row['start_date'],
                'term' => $row['term'],
                'amount' => $row['amount'],
                'car_id' => null,
                'car_edit_url' => null,
            ];

            $registrationKey = $this->extractionService->normalizeRegistrationKey($row['registration']);

            if (! $registrationKey) {
                $reportRows[] = array_merge($base, [
                    'status' => 'failed',
                    'message' => 'Registration is required.',
                ]);

                continue;
            }

            $car = $carsByRegistration[$registrationKey] ?? null;

            if (! $car) {
                $reportRows[] = array_merge($base, [
                    'status' => 'skipped',
                    'message' => 'No car found with this registration.',
                ]);

                continue;
            }

            $base['car_id'] = $car->id;
            $base['car_edit_url'] = route('cars.edit', $car->id);

            $duplicateKey = $car->id.'|'.$row['start_date'];

            if (isset($seenCarStarts[$duplicateKey])) {
                $reportRows[] = array_merge($base, [
                    'status' => 'skipped',
                    'message' => 'Duplicate road tax period for this car in this batch.',
                ]);

                continue;
            }

            $existing = CarRoadTax::query()
                ->where('car_id', $car->id)
                ->whereDate('start_date', $row['start_date'])
                ->exists();

            if ($existing) {
                $reportRows[] = array_merge($base, [
                    'status' => 'skipped',
                    'message' => 'Road tax with this start date already exists for this car.',
                ]);

                continue;
            }

            try {
                DB::transaction(function () use ($tenantId, $car, $row) {
                    CarRoadTax::create([
                        'tenant_id' => $tenantId,
                        'car_id' => $car->id,
                        'start_date' => $row['start_date'],
                        'term' => $row['term'],
                        'amount' => $row['amount'],
                    ]);
                });

                $seenCarStarts[$duplicateKey] = true;
                $carsToSyncSorn[$car->id] = $car;

                $reportRows[] = array_merge($base, [
                    'status' => 'success',
                    'message' => 'Road tax added successfully.',
                ]);
            } catch (\Throwable $e) {
                $reportRows[] = array_merge($base, [
                    'status' => 'failed',
                    'message' => 'Error saving road tax: '.$e->getMessage(),
                ]);
            }
        }

        foreach ($carsToSyncSorn as $car) {
            $car->refresh();
            $this->sornSyncService->syncAfterRoadTaxesSaved($car);
        }

        $success = count(array_filter($reportRows, fn ($r) => ($r['status'] ?? '') === 'success'));
        $skipped = count(array_filter($reportRows, fn ($r) => ($r['status'] ?? '') === 'skipped'));
        $failed = count(array_filter($reportRows, fn ($r) => ($r['status'] ?? '') === 'failed'));

        return [
            'imported_at' => now()->format('d M Y H:i'),
            'summary' => [
                'total' => count($reportRows),
                'success' => $success,
                'skipped' => $skipped,
                'failed' => $failed,
            ],
            'rows' => $reportRows,
        ];
    }

    /**
     * @return array<string, Car>
     */
    private function loadCarsByRegistration(int $tenantId): array
    {
        $map = [];

        Car::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('registration')
            ->where('registration', '!=', '')
            ->select(['id', 'registration', 'tenant_id'])
            ->chunkById(500, function ($cars) use (&$map) {
                foreach ($cars as $car) {
                    $key = $this->extractionService->normalizeRegistrationKey($car->registration);
                    if ($key) {
                        $map[$key] = $car;
                    }
                }
            });

        return $map;
    }
}
