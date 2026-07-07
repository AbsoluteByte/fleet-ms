<?php

namespace App\Support;

use App\Models\Car;
use App\Models\CarPhv;
use App\Models\CarPhvlProgress;
use Illuminate\Validation\ValidationException;

class PhvlWorkflow
{
    public const FIELD_MOT_STATUS = 'mot_status';

    public const FIELD_APPLICATION_STATUS = 'application_status';

    public const FIELD_APPLIED_DATE = 'applied_date';

    public const FIELD_APPOINTMENT_CONFIRMATION = 'appointment_confirmation';

    public const FIELD_APPOINTMENT_AT = 'appointment_at';

    public const FIELD_APPOINTMENT_NOTES = 'appointment_notes';

    public const FIELD_PHVL_RESULT = 'phvl_result_status';

    public const FIELD_FAIL_NOTES = 'fail_notes';

    public static function fieldLabels(): array
    {
        return [
            self::FIELD_MOT_STATUS => 'MOT Status',
            self::FIELD_APPLICATION_STATUS => 'Application Status',
            self::FIELD_APPLIED_DATE => 'PHVL applied date',
            self::FIELD_APPOINTMENT_CONFIRMATION => 'Appointment Confirmation',
            self::FIELD_APPOINTMENT_AT => 'Appointment Date & Time',
            self::FIELD_APPOINTMENT_NOTES => 'Appointment notes',
            self::FIELD_PHVL_RESULT => 'PHVL Status',
            self::FIELD_FAIL_NOTES => 'PHVL fail notes',
        ];
    }

    public static function stepUnlocked(?CarPhvlProgress $progress, string $field): bool
    {
        $p = $progress ?? new CarPhvlProgress([
            'mot_status' => 'pending',
            'application_status' => 'pending',
            'appointment_confirmation' => 'pending',
        ]);

        return match ($field) {
            self::FIELD_MOT_STATUS => true,
            self::FIELD_APPLICATION_STATUS => $p->mot_status === 'done',
            self::FIELD_APPLIED_DATE => $p->application_status === 'applied',
            self::FIELD_APPOINTMENT_CONFIRMATION, self::FIELD_APPOINTMENT_AT, self::FIELD_APPOINTMENT_NOTES => $p->application_status === 'applied' && $p->applied_date !== null,
            self::FIELD_PHVL_RESULT, self::FIELD_FAIL_NOTES => $p->appointment_confirmation === 'approved',
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function validateTransition(?CarPhvlProgress $progress, array $payload): void
    {
        foreach (array_keys($payload) as $field) {
            if (! self::stepUnlocked($progress, $field)) {
                throw ValidationException::withMessages([
                    $field => ['Complete the previous step before updating this field.'],
                ]);
            }
        }

        $p = $progress ?? new CarPhvlProgress;

        if (array_key_exists(self::FIELD_APPLIED_DATE, $payload)) {
            $val = $payload[self::FIELD_APPLIED_DATE];
            if ($val === null || $val === '') {
                throw ValidationException::withMessages([
                    self::FIELD_APPLIED_DATE => ['PHVL applied date is required.'],
                ]);
            }
        }

        if (array_key_exists(self::FIELD_APPOINTMENT_CONFIRMATION, $payload)
            || array_key_exists(self::FIELD_APPOINTMENT_AT, $payload)) {
            if (! $p->applied_date && empty($payload[self::FIELD_APPLIED_DATE])) {
                throw ValidationException::withMessages([
                    self::FIELD_APPLIED_DATE => ['Set PHVL applied date before appointment fields.'],
                ]);
            }
        }
    }

    public static function isWorkflowComplete(?CarPhvlProgress $progress): bool
    {
        if (! $progress) {
            return false;
        }

        return $progress->mot_status === 'done'
            && $progress->application_status === 'applied'
            && $progress->applied_date !== null
            && $progress->appointment_confirmation === 'approved'
            && in_array($progress->phvl_result_status, ['pass', 'fail'], true);
    }

    public static function isCycleComplete(?CarPhvlProgress $progress, Car $car): bool
    {
        if (! self::isWorkflowComplete($progress)) {
            return false;
        }

        return self::hasNewPhvForCurrentCycle($progress, $car);
    }

    public static function phvQualifies(CarPhv $phv): bool
    {
        return (bool) ($phv->counsel_id
            && $phv->start_date
            && $phv->expiry_date
            && $phv->amount !== null);
    }

    /**
     * True when a qualifying PHV was created during the current workflow cycle
     * (not an older record from a previous licence period).
     */
    public static function hasNewPhvForCurrentCycle(?CarPhvlProgress $progress, Car $car): bool
    {
        if (! $progress) {
            return false;
        }

        $cycleStart = $progress->updated_at ?? $progress->created_at;
        $phvs = $car->relationLoaded('phvs') ? $car->phvs : $car->phvs()->get();

        return $phvs->contains(function (CarPhv $phv) use ($cycleStart) {
            if (! self::phvQualifies($phv)) {
                return false;
            }

            return $phv->created_at && $phv->created_at->gte($cycleStart);
        });
    }

    public static function carHasQualifyingPhv(Car $car): bool
    {
        $phvs = $car->relationLoaded('phvs') ? $car->phvs : $car->phvs()->get();

        return $phvs->contains(fn (CarPhv $phv) => self::phvQualifies($phv));
    }

    public static function latestQualifyingPhv(Car $car): ?CarPhv
    {
        $phvs = $car->relationLoaded('phvs') ? $car->phvs : $car->phvs()->get();

        return $phvs
            ->filter(fn (CarPhv $phv) => self::phvQualifies($phv))
            ->sortByDesc(fn (CarPhv $p) => [optional($p->expiry_date)->timestamp ?? 0, $p->id])
            ->first();
    }

    public static function statusBtnClass(string $field, string $current, ?string $value = null): string
    {
        if (in_array($field, [self::FIELD_APPLIED_DATE, self::FIELD_APPOINTMENT_AT], true)
            && $value !== null && $value !== '') {
            return 'btn-outline-success';
        }

        return match ($current) {
            'done', 'applied', 'approved', 'pass' => 'btn-outline-success',
            'fail', 'additional_documents' => 'btn-outline-warning',
            default => 'btn-outline-secondary',
        };
    }
}
