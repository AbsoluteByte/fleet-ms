<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarPhv;
use App\Models\CarPhvlArchive;
use App\Models\CarPhvlProgress;
use App\Models\CarPhvlProgressEvent;
use App\Support\PhvlWorkflow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PhvlArchiveService
{
    /**
     * Archive only when workflow is complete and a new PHV was just inserted.
     */
    public static function tryArchiveAfterNewPhv(Car $car, CarPhv $newPhv): ?CarPhvlArchive
    {
        if (! PhvlWorkflow::phvQualifies($newPhv)) {
            return null;
        }

        $car->loadMissing(['phvlProgress', 'phvs.counsel', 'company', 'carModel']);

        $progress = $car->phvlProgress;
        if (! PhvlWorkflow::isWorkflowComplete($progress)) {
            return null;
        }

        $cycleStart = $progress->updated_at ?? $progress->created_at;
        if (! $newPhv->created_at || $newPhv->created_at->lt($cycleStart)) {
            return null;
        }

        return self::archive($car, $progress, $newPhv);
    }

    public static function archive(Car $car, CarPhvlProgress $progress, CarPhv $phv): CarPhvlArchive
    {
        return DB::transaction(function () use ($car, $progress, $phv) {
            $renewalContext = self::buildRenewalContext($car, $phv);

            $archive = CarPhvlArchive::create([
                'tenant_id' => $progress->tenant_id,
                'car_id' => $car->id,
                'car_phv_id' => $phv->id,
                'mot_status' => $progress->mot_status,
                'application_status' => $progress->application_status,
                'applied_date' => $progress->applied_date,
                'appointment_confirmation' => $progress->appointment_confirmation,
                'appointment_notes' => $progress->appointment_notes,
                'appointment_at' => $progress->appointment_at,
                'phvl_result_status' => $progress->phvl_result_status,
                'fail_notes' => $progress->fail_notes,
                'renewal_context' => $renewalContext,
                'phv_summary' => self::phvSummary($phv),
                'completed_at' => now(),
                'completed_by' => Auth::id(),
            ]);

            CarPhvlProgressEvent::query()
                ->where('car_id', $car->id)
                ->whereNull('archive_id')
                ->update(['archive_id' => $archive->id]);

            $progress->update([
                'mot_status' => 'pending',
                'application_status' => 'pending',
                'applied_date' => null,
                'appointment_confirmation' => 'pending',
                'appointment_notes' => null,
                'appointment_at' => null,
                'phvl_result_status' => null,
                'fail_notes' => null,
                'updated_by' => Auth::id(),
            ]);

            return $archive;
        });
    }

    private static function buildRenewalContext(Car $car, ?CarPhv $phv): ?string
    {
        if (! $phv?->expiry_date) {
            return null;
        }

        $today = now()->startOfDay();
        $exp = $phv->expiry_date->copy()->startOfDay();

        if ($exp->lt($today)) {
            $daysAgo = (int) $exp->diffInDays($today);

            return 'Expired '.$daysAgo.' days ago';
        }

        $daysUntil = (int) $today->diffInDays($exp);

        return 'Expires in '.$daysUntil.' days';
    }

    /**
     * @return array<string, mixed>
     */
    private static function phvSummary(CarPhv $phv): array
    {
        $phv->loadMissing('counsel');

        return [
            'counsel' => $phv->counsel?->name,
            'amount' => $phv->amount,
            'start_date' => $phv->start_date?->format('Y-m-d'),
            'expiry_date' => $phv->expiry_date?->format('Y-m-d'),
            'notify_before_expiry' => $phv->notify_before_expiry,
        ];
    }
}
