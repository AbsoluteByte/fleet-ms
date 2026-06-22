<?php

namespace App\Services;

use App\Models\CarInsurance;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InsuranceDateRangeReportService
{
    public function __construct(
        private readonly InsuranceStatusResolver $statusResolver,
    ) {}

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
     * @return Collection<int, object>
     */
    public function activatedStillActive(
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
            ->filter(function (CarInsurance $policy) {
                $car = $policy->car;
                if (! $car) {
                    return false;
                }

                $active = $car->currentActiveInsurance();

                return $active && (int) $active->id === (int) $policy->id;
            })
            ->values()
            ->map(fn (CarInsurance $policy) => $this->mapRow($policy));
    }

    /**
     * @return Collection<int, object>
     */
    public function activatedAndEndedInRange(
        int $tenantId,
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $companyId = null,
        ?int $insuranceProviderId = null,
    ): Collection {
        $cancelledStatusIds = $this->statusResolver->cancelledStatusIds();

        return $this->baseQuery($tenantId, $companyId, $insuranceProviderId)
            ->whereNotNull('start_date')
            ->whereNotNull('canceled_date')
            ->whereIn('status_id', $cancelledStatusIds)
            ->whereDate('start_date', '>=', $from->toDateString())
            ->whereDate('start_date', '<=', $to->toDateString())
            ->whereDate('canceled_date', '>=', $from->toDateString())
            ->whereDate('canceled_date', '<=', $to->toDateString())
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->map(fn (CarInsurance $policy) => $this->mapRow($policy));
    }

    /**
     * @return Collection<int, object>
     */
    public function preExistingPolicies(
        int $tenantId,
        CarbonInterface $from,
        CarbonInterface $to,
        ?int $companyId = null,
        ?int $insuranceProviderId = null,
    ): Collection {
        return $this->baseQuery($tenantId, $companyId, $insuranceProviderId)
            ->whereNotNull('start_date')
            ->whereDate('start_date', '<', $from->toDateString())
            ->where(function ($q) use ($from) {
                $q->whereNull('canceled_date')
                    ->orWhereDate('canceled_date', '>', $from->toDateString());
            })
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
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
