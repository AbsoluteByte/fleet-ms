<?php

namespace App\Services;

use App\Models\FleetNotificationDismissal;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class FleetNotificationDismissalService
{
    public const DISMISS_ALLOWED_EMAIL = 'jawad@samoretraders.com';

    public function canDismiss(?User $user = null): bool
    {
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        return strtolower(trim((string) $user->email)) === self::DISMISS_ALLOWED_EMAIL;
    }

    /**
     * @return Collection<int, FleetNotificationDismissal>
     */
    public function dismissedForUser(User $user, Tenant $tenant): Collection
    {
        return FleetNotificationDismissal::query()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->get();
    }

    public function dismiss(User $user, Tenant $tenant, array $notification): FleetNotificationDismissal
    {
        $sourceExpiryDate = $this->expiryDateFromNotification($notification);

        return FleetNotificationDismissal::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'notification_type' => (string) ($notification['type'] ?? ''),
                'source_record_id' => (int) ($notification['source_record_id'] ?? 0),
                'source_expiry_date' => $sourceExpiryDate,
            ],
            [
                'car_id' => isset($notification['car_id']) ? (int) $notification['car_id'] : null,
                'driver_id' => isset($notification['driver_id']) ? (int) $notification['driver_id'] : null,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $notification
     */
    public function shouldHide(array $notification, Collection $dismissals): bool
    {
        if ($dismissals->isEmpty()) {
            return false;
        }

        $type = (string) ($notification['type'] ?? '');
        $sourceRecordId = (int) ($notification['source_record_id'] ?? 0);
        $expiryDate = $this->expiryDateFromNotification($notification);

        return $dismissals->contains(function (FleetNotificationDismissal $dismissal) use ($type, $sourceRecordId, $expiryDate, $notification) {
            if ($dismissal->notification_type !== $type) {
                return false;
            }

            if ((int) $dismissal->source_record_id !== $sourceRecordId) {
                return false;
            }

            $dismissedExpiry = $dismissal->source_expiry_date?->toDateString();
            $notificationExpiry = $expiryDate?->toDateString();

            if ($dismissedExpiry !== $notificationExpiry) {
                return false;
            }

            $notificationCarId = isset($notification['car_id']) ? (int) $notification['car_id'] : null;
            $notificationDriverId = isset($notification['driver_id']) ? (int) $notification['driver_id'] : null;

            if ($dismissal->car_id !== null && $notificationCarId !== null && (int) $dismissal->car_id !== $notificationCarId) {
                return false;
            }

            if ($dismissal->driver_id !== null && $notificationDriverId !== null && (int) $dismissal->driver_id !== $notificationDriverId) {
                return false;
            }

            return true;
        });
    }

    /**
     * @param  array<string, mixed>  $notification
     */
    public function expiryDateFromNotification(array $notification): ?Carbon
    {
        if (! isset($notification['sort_key'])) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $notification['sort_key'])->startOfDay();
    }
}
