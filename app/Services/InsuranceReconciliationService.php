<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarInsurance;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class InsuranceReconciliationService
{
    public function __construct(
        private readonly FleetPolicyScheduleParser $parser,
    ) {}

    /**
     * @return array{
     *     from: string,
     *     to: string,
     *     pdf: array{inception: ?string, expiry: ?string, policy_number: ?string, vehicle_count: int},
     *     summary: array<string, int>,
     *     matched: list<array<string, mixed>>,
     *     pdf_only: list<array<string, mixed>>,
     *     system_only: list<array<string, mixed>>,
     *     pdf_duplicates: list<array<string, mixed>>,
     *     system_duplicates: list<array<string, mixed>>
     * }
     */
    public function reconcile(int $tenantId, CarbonInterface $from, CarbonInterface $to, string $pdfPath): array
    {
        return $this->reconcileParsed($tenantId, $from, $to, $this->parser->parseFile($pdfPath));
    }

    /**
     * @param  array{
     *     inception: ?\Carbon\Carbon,
     *     expiry: ?\Carbon\Carbon,
     *     policy_number: ?string,
     *     vehicles: list<array<string, mixed>>,
     *     raw_text_length?: int
     * }  $parsed
     * @return array{
     *     from: string,
     *     to: string,
     *     pdf: array{inception: ?string, expiry: ?string, policy_number: ?string, vehicle_count: int},
     *     summary: array<string, int>,
     *     matched: list<array<string, mixed>>,
     *     pdf_only: list<array<string, mixed>>,
     *     system_only: list<array<string, mixed>>,
     *     pdf_duplicates: list<array<string, mixed>>,
     *     system_duplicates: list<array<string, mixed>>
     * }
     */
    public function reconcileParsed(int $tenantId, CarbonInterface $from, CarbonInterface $to, array $parsed): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        $pdfRowsByKey = [];
        $pdfCounts = [];

        foreach ($parsed['vehicles'] as $vehicle) {
            $key = $vehicle['registration_key'];
            $pdfCounts[$key] = ($pdfCounts[$key] ?? 0) + 1;
            $pdfRowsByKey[$key][] = $vehicle;
        }

        $systemPolicies = $this->overlappingPolicies($tenantId, $from, $to);
        $systemByKey = $systemPolicies->groupBy(
            fn (CarInsurance $policy) => $this->parser->normalizeRegistration((string) ($policy->car?->registration ?? ''))
        )->filter(fn (Collection $group, string $key) => $key !== '');

        $fleetCarsByKey = Car::query()
            ->where('tenant_id', $tenantId)
            ->get(['id', 'registration'])
            ->mapWithKeys(function (Car $car) {
                $key = $this->parser->normalizeRegistration((string) $car->registration);

                return $key !== '' ? [$key => $car] : [];
            });

        $matched = [];
        $pdfOnly = [];
        $pdfDuplicates = [];
        $systemOnly = [];
        $systemDuplicates = [];

        foreach ($pdfRowsByKey as $key => $rows) {
            $first = $rows[0];
            $systemGroup = $systemByKey->get($key, collect());
            $fleetCar = $fleetCarsByKey->get($key);

            if (($pdfCounts[$key] ?? 0) > 1) {
                $pdfDuplicates[] = $this->pdfRowPayload($first, $pdfCounts[$key], $fleetCar, $systemGroup);
            }

            if ($systemGroup->isNotEmpty()) {
                $matched[] = $this->matchedPayload($first, $systemGroup);
            } else {
                $pdfOnly[] = $this->pdfRowPayload($first, $pdfCounts[$key] ?? 1, $fleetCar, $systemGroup);
            }
        }

        foreach ($systemByKey as $key => $systemGroup) {
            if ($systemGroup->count() > 1) {
                $systemDuplicates[] = $this->systemDuplicatePayload($key, $systemGroup);
            }

            if (! isset($pdfRowsByKey[$key])) {
                $systemOnly[] = $this->systemOnlyPayload($key, $systemGroup);
            }
        }

        usort($matched, fn ($a, $b) => strcmp($a['registration'], $b['registration']));
        usort($pdfOnly, fn ($a, $b) => strcmp($a['registration'], $b['registration']));
        usort($systemOnly, fn ($a, $b) => strcmp($a['registration'], $b['registration']));
        usort($pdfDuplicates, fn ($a, $b) => strcmp($a['registration'], $b['registration']));
        usort($systemDuplicates, fn ($a, $b) => strcmp($a['registration'], $b['registration']));

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'pdf' => [
                'inception' => $parsed['inception']?->toDateString(),
                'expiry' => $parsed['expiry']?->toDateString(),
                'policy_number' => $parsed['policy_number'],
                'vehicle_count' => count($parsed['vehicles']),
            ],
            'summary' => [
                'matched' => count($matched),
                'pdf_only' => count($pdfOnly),
                'system_only' => count($systemOnly),
                'pdf_duplicates' => count($pdfDuplicates),
                'system_duplicates' => count($systemDuplicates),
                'pdf_vehicle_rows' => count($parsed['vehicles']),
                'pdf_unique_regs' => count($pdfRowsByKey),
                'system_unique_regs' => $systemByKey->count(),
            ],
            'matched' => $matched,
            'pdf_only' => $pdfOnly,
            'system_only' => $systemOnly,
            'pdf_duplicates' => $pdfDuplicates,
            'system_duplicates' => $systemDuplicates,
        ];
    }

    /**
     * @return Collection<int, CarInsurance>
     */
    private function overlappingPolicies(int $tenantId, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return CarInsurance::query()
            ->whereHas('car', fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereNotNull('start_date')
            ->whereDate('start_date', '<=', $to->toDateString())
            ->where(function ($query) use ($from) {
                $query->whereNull('expiry_date')
                    ->orWhereDate('expiry_date', '>=', $from->toDateString());
            })
            ->where(function ($query) use ($from) {
                $query->whereNull('canceled_date')
                    ->orWhereDate('canceled_date', '>=', $from->toDateString());
            })
            ->with(['car.company', 'car.carModel', 'status', 'insuranceProvider'])
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $pdfRow
     * @param  Collection<int, CarInsurance>  $systemGroup
     * @return array<string, mixed>
     */
    private function matchedPayload(array $pdfRow, Collection $systemGroup): array
    {
        $policy = $systemGroup->first();

        return [
            'registration' => $pdfRow['registration'],
            'pdf_make_model' => $pdfRow['make_model'],
            'pdf_cover' => $pdfRow['cover'],
            'pdf_annual_rate' => $pdfRow['annual_rate'],
            'pdf_date_added' => optional($pdfRow['date_added'])->toDateString(),
            'system_start' => optional($policy?->start_date)->toDateString(),
            'system_expiry' => optional($policy?->expiry_date)->toDateString(),
            'system_status' => $policy?->status?->name,
            'system_provider' => $policy?->insuranceProvider?->provider_name,
            'system_policy_count' => $systemGroup->count(),
            'car_id' => $policy?->car_id,
        ];
    }

    /**
     * @param  array<string, mixed>  $pdfRow
     * @param  Collection<int, CarInsurance>  $systemGroup
     * @return array<string, mixed>
     */
    private function pdfRowPayload(array $pdfRow, int $pdfCount, ?Car $fleetCar, Collection $systemGroup): array
    {
        return [
            'registration' => $pdfRow['registration'],
            'pdf_make_model' => $pdfRow['make_model'],
            'pdf_cover' => $pdfRow['cover'],
            'pdf_annual_rate' => $pdfRow['annual_rate'],
            'pdf_date_added' => optional($pdfRow['date_added'])->toDateString(),
            'pdf_count' => $pdfCount,
            'car_exists' => $fleetCar !== null,
            'car_id' => $fleetCar?->id,
            'note' => $fleetCar
                ? ($systemGroup->isEmpty() ? 'Car exists in fleet but no overlapping insurance for this period' : null)
                : 'Registration not found in fleet',
        ];
    }

    /**
     * @param  Collection<int, CarInsurance>  $systemGroup
     * @return array<string, mixed>
     */
    private function systemOnlyPayload(string $registrationKey, Collection $systemGroup): array
    {
        $policy = $systemGroup->first();

        return [
            'registration' => $registrationKey,
            'system_start' => optional($policy?->start_date)->toDateString(),
            'system_expiry' => optional($policy?->expiry_date)->toDateString(),
            'system_canceled' => optional($policy?->canceled_date)->toDateString(),
            'system_status' => $policy?->status?->name,
            'system_provider' => $policy?->insuranceProvider?->provider_name,
            'system_policy_count' => $systemGroup->count(),
            'car_id' => $policy?->car_id,
            'company' => $policy?->car?->company?->name,
            'model' => $policy?->car?->carModel?->name,
        ];
    }

    /**
     * @param  Collection<int, CarInsurance>  $systemGroup
     * @return array<string, mixed>
     */
    private function systemDuplicatePayload(string $registrationKey, Collection $systemGroup): array
    {
        return [
            'registration' => $registrationKey,
            'system_policy_count' => $systemGroup->count(),
            'car_id' => $systemGroup->first()?->car_id,
            'policies' => $systemGroup->map(fn (CarInsurance $policy) => [
                'id' => $policy->id,
                'start_date' => optional($policy->start_date)->toDateString(),
                'expiry_date' => optional($policy->expiry_date)->toDateString(),
                'canceled_date' => optional($policy->canceled_date)->toDateString(),
                'status' => $policy->status?->name,
                'provider' => $policy->insuranceProvider?->provider_name,
            ])->values()->all(),
        ];
    }
}
