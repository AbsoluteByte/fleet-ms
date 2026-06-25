<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarMot;
use App\Support\PhvlMotHelper;
use App\Support\SimpleSpreadsheetReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MotTestDateImportService
{
    public function __construct(
        private readonly InsuranceCertificateParser $registrationParser,
    ) {}

    /**
     * @return array{
     *     imported_at: string,
     *     filename: string,
     *     summary: array{total: int, success: int, skipped: int, failed: int},
     *     rows: list<array<string, mixed>>
     * }
     */
    public function import(string $filePath, int $tenantId): array
    {
        @set_time_limit(300);

        $rows = SimpleSpreadsheetReader::read($filePath);
        $parsedRows = $this->parseRows($rows);
        $carsByRegistration = $this->loadCarsByRegistration($tenantId);
        $seenRegistrations = [];
        $reportRows = [];

        foreach ($parsedRows as $row) {
            $reportRows[] = $this->processRow($row, $carsByRegistration, $seenRegistrations);
        }

        return [
            'imported_at' => now()->toDateTimeString(),
            'filename' => basename($filePath),
            'summary' => [
                'total' => count($reportRows),
                'success' => count(array_filter($reportRows, fn ($r) => $r['status'] === 'success')),
                'skipped' => count(array_filter($reportRows, fn ($r) => $r['status'] === 'skipped')),
                'failed' => count(array_filter($reportRows, fn ($r) => $r['status'] === 'failed')),
            ],
            'rows' => $reportRows,
        ];
    }

    /**
     * @param  list<list<string>>  $rows
     * @return list<array{
     *     row_number: int,
     *     registration: string|null,
     *     test_date: string|null,
     *     expiry_date: string|null
     * }>
     */
    private function parseRows(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $rows[0]);
        $registrationIndex = $this->findColumnIndex($headers, ['carreg', 'registration', 'reg']);
        $testDateIndex = $this->findColumnIndex($headers, ['mottestdate', 'testdate']);
        $expiryDateIndex = $this->findColumnIndex($headers, ['motexpirydate', 'expirydate', 'motexpiry']);

        if ($registrationIndex === null || $testDateIndex === null) {
            throw new \InvalidArgumentException('Spreadsheet must include CAR REG and MOT TEST DATE columns.');
        }

        $parsed = [];

        foreach (array_slice($rows, 1) as $offset => $row) {
            $registration = trim((string) ($row[$registrationIndex] ?? ''));
            $testDate = trim((string) ($row[$testDateIndex] ?? ''));
            $expiryDate = $expiryDateIndex !== null
                ? trim((string) ($row[$expiryDateIndex] ?? ''))
                : '';

            if ($registration === '' && $testDate === '' && $expiryDate === '') {
                continue;
            }

            $parsed[] = [
                'row_number' => $offset + 2,
                'registration' => $registration !== '' ? $registration : null,
                'test_date' => $testDate !== '' ? $testDate : null,
                'expiry_date' => $expiryDate !== '' ? $expiryDate : null,
            ];
        }

        return $parsed;
    }

    /**
     * @param  array<string, Car>  $carsByRegistration
     * @param  array<string, true>  $seenRegistrations
     * @return array<string, mixed>
     */
    private function processRow(array $row, array $carsByRegistration, array &$seenRegistrations): array
    {
        $base = [
            'row_number' => $row['row_number'],
            'registration' => $row['registration'],
            'test_date' => null,
            'previous_test_date' => null,
            'mot_expiry_date' => null,
            'file_expiry_date' => null,
            'car_id' => null,
            'car_edit_url' => null,
            'mot_id' => null,
        ];

        if (! $row['registration']) {
            return array_merge($base, [
                'status' => 'failed',
                'message' => 'Registration is required.',
            ]);
        }

        $registrationKey = $this->registrationParser->normalizeRegistration($row['registration']);
        $base['registration'] = $row['registration'];

        if (isset($seenRegistrations[$registrationKey])) {
            return array_merge($base, [
                'status' => 'skipped',
                'message' => 'Duplicate registration in this upload batch.',
            ]);
        }

        $seenRegistrations[$registrationKey] = true;

        if (! $row['test_date']) {
            return array_merge($base, [
                'status' => 'failed',
                'message' => 'MOT test date is required.',
            ]);
        }

        $testDate = $this->parseDate($row['test_date']);

        if (! $testDate) {
            return array_merge($base, [
                'status' => 'failed',
                'message' => 'Could not parse MOT test date.',
            ]);
        }

        $base['test_date'] = $testDate->format('d M, Y');

        if ($row['expiry_date']) {
            $fileExpiry = $this->parseDate($row['expiry_date']);
            $base['file_expiry_date'] = $fileExpiry?->format('d M, Y') ?? $row['expiry_date'];
        }

        $car = $carsByRegistration[$registrationKey] ?? null;

        if (! $car) {
            return array_merge($base, [
                'status' => 'skipped',
                'message' => 'No car found with this registration.',
            ]);
        }

        $base['car_id'] = $car->id;
        $base['car_edit_url'] = route('cars.edit', $car->id);

        $mot = PhvlMotHelper::latestMot($car);

        if (! $mot) {
            return array_merge($base, [
                'status' => 'skipped',
                'message' => 'No MOT entry found for this car.',
            ]);
        }

        $base['mot_id'] = $mot->id;
        $base['previous_test_date'] = $mot->test_date?->format('d M, Y') ?? '—';
        $base['mot_expiry_date'] = $mot->expiry_date?->format('d M, Y') ?? '—';

        if ($mot->test_date?->isSameDay($testDate)) {
            return array_merge($base, [
                'status' => 'skipped',
                'message' => 'Latest MOT already has this test date.',
            ]);
        }

        try {
            DB::transaction(function () use ($mot, $testDate) {
                /** @var CarMot $lockedMot */
                $lockedMot = CarMot::query()->whereKey($mot->id)->lockForUpdate()->firstOrFail();
                $lockedMot->test_date = $testDate->toDateString();
                $lockedMot->save();
            });
        } catch (\Throwable $e) {
            return array_merge($base, [
                'status' => 'failed',
                'message' => 'Error updating MOT test date: '.$e->getMessage(),
            ]);
        }

        $message = $base['previous_test_date'] === '—'
            ? 'Set test date on latest MOT entry.'
            : 'Updated latest MOT test date.';

        return array_merge($base, [
            'status' => 'success',
            'message' => $message,
        ]);
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
                    $key = $this->registrationParser->normalizeRegistration($car->registration);
                    $map[$key] = $car;
                }
            });

        return $map;
    }

    private function normalizeHeader(string $header): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($header)) ?? '';
    }

    /**
     * @param  list<string>  $headers
     * @param  list<string>  $candidates
     */
    private function findColumnIndex(array $headers, array $candidates): ?int
    {
        foreach ($candidates as $candidate) {
            $index = array_search($candidate, $headers, true);

            if ($index !== false) {
                return $index;
            }
        }

        return null;
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        foreach (['d.m.Y', 'd/m/Y', 'd-m-Y', 'Y-m-d', 'j.n.Y', 'j/n/Y', 'j-n-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
            }
        }

        if (is_numeric($value)) {
            try {
                return Carbon::createFromTimestampUTC(((int) $value - 25569) * 86400)->startOfDay();
            } catch (\Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
