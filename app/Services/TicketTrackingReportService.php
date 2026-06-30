<?php

namespace App\Services;

use App\Models\Agreement;
use App\Models\Car;
use Carbon\Carbon;

class TicketTrackingReportService
{
    public function findAssignment(int $tenantId, int $carId, Carbon $at): ?Agreement
    {
        $carExists = Car::query()
            ->where('tenant_id', $tenantId)
            ->where('id', $carId)
            ->exists();

        if (! $carExists) {
            return null;
        }

        return Agreement::query()
            ->where('tenant_id', $tenantId)
            ->where('car_id', $carId)
            ->where('start_date', '<=', $at)
            ->whereDate('end_date', '>=', $at->toDateString())
            ->whereHas('status', fn ($query) => $query->where('name', '!=', 'Pending'))
            ->with(['driver', 'status', 'car.carModel', 'upgradedToAgreement'])
            ->get()
            ->filter(fn (Agreement $agreement) => $agreement->isAssignedAt($at))
            ->sortByDesc(fn (Agreement $agreement) => $agreement->start_date?->timestamp ?? 0)
            ->first();
    }
}
