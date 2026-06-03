<?php

namespace App\Services;

use App\Models\Status;

class InsuranceStatusResolver
{
    public function activeStatusId(): int
    {
        return $this->statusIdByName('Active');
    }

    public function cancelledStatusId(): int
    {
        $status = Status::query()
            ->where('type', 'insurance')
            ->whereIn('name', ['Cancelled', 'Canceled'])
            ->orderByRaw("CASE name WHEN 'Cancelled' THEN 0 ELSE 1 END")
            ->first();

        if ($status) {
            return (int) $status->id;
        }

        return $this->statusIdByName('Cancelled');
    }

    /**
     * @return int[]
     */
    public function cancelledStatusIds(): array
    {
        $ids = Status::query()
            ->where('type', 'insurance')
            ->whereIn('name', ['Cancelled', 'Canceled'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($ids !== []) {
            return $ids;
        }

        return [$this->cancelledStatusId()];
    }

    public function statusIdByName(string $statusName): int
    {
        $status = Status::firstOrCreate(
            ['type' => 'insurance', 'name' => $statusName],
            ['color' => $statusName === 'Applied' ? '#17a2b8' : '#28a745']
        );

        return (int) $status->id;
    }
}
