<?php

namespace App\Support;

use App\Models\Car;
use App\Models\CarMot;
use Carbon\Carbon;

class PhvlMotHelper
{
    public static function latestMot(Car $car): ?CarMot
    {
        if ($car->relationLoaded('mots')) {
            return $car->mots
                ->sortByDesc(fn (CarMot $m) => [optional($m->expiry_date)->timestamp ?? 0, $m->id])
                ->first();
        }

        return $car->mots()
            ->orderByDesc('expiry_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Extract first integer from MOT term text (e.g. "12 months" -> 12).
     */
    public static function termMonths(?string $term): int
    {
        if ($term === null || $term === '') {
            return 0;
        }

        if (preg_match('/(\d+)/', (string) $term, $m)) {
            return min(120, max(0, (int) $m[1]));
        }

        return 0;
    }

    public static function estimatedMotDate(?CarMot $mot): ?Carbon
    {
        if ($mot?->test_date) {
            return $mot->test_date->copy()->startOfDay();
        }

        if (! $mot?->expiry_date) {
            return null;
        }

        $months = self::termMonths($mot->term);

        return $mot->expiry_date->copy()->subMonths($months)->startOfDay();
    }

    /**
     * Whole days since estimated MOT date; 0 if MOT date is in the future; null if no MOT.
     */
    public static function motDaysOld(?CarMot $mot): ?int
    {
        $motDate = self::estimatedMotDate($mot);
        if (! $motDate) {
            return null;
        }

        $today = now()->startOfDay();
        if ($motDate->gt($today)) {
            return 0;
        }

        return (int) $motDate->diffInDays($today);
    }

    public static function motDaysOldWithStale(?CarMot $mot): bool
    {
        $d = self::motDaysOld($mot);

        return $d !== null && $d > 10;
    }
}
