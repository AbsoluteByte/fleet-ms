<?php

namespace App\Services;

use App\Models\CarPhvlProgress;
use App\Models\CarPhvlProgressEvent;
use App\Support\PhvlWorkflow;
use Illuminate\Support\Facades\Auth;

class PhvlProgressEventLogger
{
    /**
     * @param  array<string, mixed>  $changes  field => new value (already applied on model)
     */
    public static function logChanges(CarPhvlProgress $progress, array $changes, ?array $original = null): void
    {
        $userId = Auth::id();
        $tenantId = $progress->tenant_id;
        $original = $original ?? $progress->getOriginal();

        foreach ($changes as $field => $newValue) {
            if (! array_key_exists($field, PhvlWorkflow::fieldLabels())) {
                continue;
            }

            $oldValue = $original[$field] ?? null;
            $oldStr = self::stringify($oldValue);
            $newStr = self::stringify($newValue);

            if ($oldStr === $newStr) {
                continue;
            }

            CarPhvlProgressEvent::create([
                'tenant_id' => $tenantId,
                'car_id' => $progress->car_id,
                'archive_id' => null,
                'field' => $field,
                'old_value' => $oldStr,
                'new_value' => $newStr,
                'user_id' => $userId,
            ]);
        }
    }

    private static function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
