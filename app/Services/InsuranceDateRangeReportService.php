<?php

namespace App\Services;

use App\Models\CarInsurance;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InsuranceDateRangeReportService
{
    /**
     * @return Collection<int, object>
     */
    public function removedInRange(
        int $tenantId,
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $companyId = null,
        ?int $insuranceProviderId = null,
    ): Collection {
        return $this->baseQuery($tenantId, $companyId, $insuranceProviderId)
            ->whereNotNull('canceled_date')
            ->whereDate('canceled_date', '>=', $from->toDateString())
            ->whereDate('canceled_date', '<=', $to->toDateString())
            ->orderBy('canceled_date')
            ->orderBy('id')
            ->get()
            ->map(fn (CarInsurance $policy) => $this->mapRow($policy));
    }

    /**
     * Policies whose start_date falls in the selected range (whether still active or not).
     *
     * @return Collection<int, object>
     */
    public function activatedInRange(
        int $tenantId,
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $companyId = null,
        ?int $insuranceProviderId = null,
    ): Collection {
        return $this->baseQuery($tenantId, $companyId, $insuranceProviderId)
            ->whereNotNull('start_date')
            ->whereDate('start_date', '>=', $from->toDateString())
            ->whereDate('start_date', '<=', $to->toDateString())
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->map(fn (CarInsurance $policy) => $this->mapRow($policy));
    }

    /**
     * Full merge of removed-in-range and activated-in-range (duplicates allowed).
     *
     * @return Collection<int, object>
     */
    public function activatedOrRemovedInRange(
        int $tenantId,
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $companyId = null,
        ?int $insuranceProviderId = null,
    ): Collection {
        return $this->removedInRange($tenantId, $from, $to, $companyId, $insuranceProviderId)
            ->merge($this->activatedInRange($tenantId, $from, $to, $companyId, $insuranceProviderId))
            ->values();
    }

    /**
     * Currently active insurance policies. Date range is not applied.
     *
     * @return Collection<int, object>
     */
    public function activeOnInsurance(
        int $tenantId,
        ?int $companyId = null,
        ?int $insuranceProviderId = null,
    ): Collection {
        return $this->baseQuery($tenantId, $companyId, $insuranceProviderId)
            ->whereNotNull('start_date')
            ->orderBy('id')
            ->get()
            ->filter(function (CarInsurance $policy) {
                $car = $policy->car;
                if (! $car) {
                    return false;
                }

                $active = $car->currentActiveInsurance();

                return $active && (int) $active->id === (int) $policy->id;
            })
            ->sortBy([
                fn ($policy) => strtolower((string) ($policy->car?->registration ?? '')),
                fn ($policy) => optional($policy->start_date)->timestamp ?? 0,
                fn ($policy) => $policy->id,
            ])
            ->values()
            ->map(fn (CarInsurance $policy) => $this->mapRow($policy));
    }

    private function baseQuery(int $tenantId, ?int $companyId, ?int $insuranceProviderId): Builder
    {
        return CarInsurance::query()
            ->whereHas('car', function ($q) use ($tenantId, $companyId) {
                $q->where('tenant_id', $tenantId);
                if ($companyId) {
                    $q->where('company_id', $companyId);
                }
            })
            ->when($insuranceProviderId, fn (Builder $q) => $q->where('insurance_provider_id', $insuranceProviderId))
            ->with($this->eagerLoads());
    }

    /**
     * @return array<int, string>
     */
    private function eagerLoads(): array
    {
        return [
            'car.company',
            'car.carModel',
            'car.insurances.status',
            'insuranceProvider',
            'status',
        ];
    }

    private function mapRow(CarInsurance $policy): object
    {
        $car = $policy->car;

        return (object) [
            'policy_id' => $policy->id,
            'car_id' => $car?->id,
            'registration' => $car?->registration ?? '—',
            'company' => $car?->company?->name ?? '—',
            'model' => $car?->carModel?->name ?? '—',
            'provider' => $policy->insuranceProvider?->provider_name ?? '—',
            'start_date' => $policy->start_date,
            'expiry_date' => $policy->expiry_date,
            'canceled_date' => $policy->canceled_date,
            'current_status' => $this->currentStatusLabel($car),
            'policy_status' => trim((string) optional($policy->status)->name) ?: '—',
        ];
    }

    private function currentStatusLabel($car): string
    {
        if (! $car) {
            return 'Inactive';
        }

        $latestInsurance = $car->insurances
            ->sortByDesc(fn (CarInsurance $i) => [optional($i->created_at)->timestamp ?? 0, $i->id])
            ->first();

        $statusName = trim((string) optional(optional($latestInsurance)->status)->name);

        if (strcasecmp($statusName, 'Applied') === 0) {
            return 'Applied';
        }

        if (strcasecmp($statusName, 'Active') === 0) {
            return 'Active';
        }

        return 'Inactive';
    }

    public function parseDateRange(?string $from, ?string $to): ?array
    {
        if ($from === null || $from === '' || $to === null || $to === '') {
            return null;
        }

        try {
            $fromDate = Carbon::parse($from)->startOfDay();
            $toDate = Carbon::parse($to)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($fromDate->gt($toDate)) {
            return null;
        }

        return [$fromDate, $toDate];
    }
}
