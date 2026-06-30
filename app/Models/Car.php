<?php

// app/Models/Car.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    use HasFactory;

    public const FLEET_STATUS_PREPARATION_FOR_PHVL = 'preparation_for_phvl';

    public const FLEET_STATUS_AVAILABLE_FOR_RENT = 'available_for_rent';

    public const FLEET_STATUS_NON_COMPLIANT = 'non_compliant';

    public const FLEET_STATUS_WRITTEN_OFF = 'written_off';

    public const FLEET_STATUS_STOLEN = 'stolen';

    public const FLEET_STATUS_SOLD = 'sold';

    protected $fillable = [
        'tenant_id', 'company_id', 'car_model_id', 'registration', 'color',
        'vin', 'v5_document', 'manufacture_year', 'registration_year',
        'purchase_date', 'purchase_price', 'purchase_type', 'seller_name',
        'seller_notes', 'damaged_notes', 'phv_status', 'phv_applied_date', 'phv_applied_by',
        'phvl_suspension_status', 'phvl_suspension_status_date',
        'log_book_applied', 'log_book_applied_date', 'logbook_notes', 'old_log_book',
        'log_book_applied_by',
        'sorn_applied', 'sorn_applied_at', 'sorn_applied_by', 'sorn_document',
        'fleet_status', 'available_from_date',
        'createdBy', 'updatedBy',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'log_book_applied' => 'boolean',
        'log_book_applied_date' => 'date',
        'old_log_book' => 'array',
        'v5_document' => 'array',
        'sorn_applied' => 'boolean',
        'sorn_applied_at' => 'datetime',
        'available_from_date' => 'date',
        'phv_applied_date' => 'date',
        'phvl_suspension_status_date' => 'date',
    ];

    // ==================== RELATIONSHIPS ====================

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function carModel()
    {
        return $this->belongsTo(CarModel::class);
    }

    public function logBookAppliedBy()
    {
        return $this->belongsTo(User::class, 'log_book_applied_by');
    }

    public function sornAppliedBy()
    {
        return $this->belongsTo(User::class, 'sorn_applied_by');
    }

    public function phvAppliedBy()
    {
        return $this->belongsTo(User::class, 'phv_applied_by');
    }

    public function agreements()
    {
        return $this->hasMany(Agreement::class);
    }

    public function claims()
    {
        return $this->hasMany(Claim::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function mots()
    {
        return $this->hasMany(CarMot::class);
    }

    public function roadTaxes()
    {
        return $this->hasMany(CarRoadTax::class);
    }

    public function phvs()
    {
        return $this->hasMany(CarPhv::class);
    }

    public function phvlProgress()
    {
        return $this->hasOne(CarPhvlProgress::class);
    }

    public function insurances()
    {
        return $this->hasMany(CarInsurance::class);
    }

    public function services()
    {
        return $this->hasMany(CarService::class);
    }

    public function reservations()
    {
        return $this->hasMany(CarReservation::class);
    }

    public function vehicleSwapsAsOld()
    {
        return $this->hasMany(VehicleSwap::class, 'old_car_id');
    }

    public function vehicleSwapsAsNew()
    {
        return $this->hasMany(VehicleSwap::class, 'swapped_with_car_id');
    }

    public function statusHistories()
    {
        return $this->hasMany(CarStatusHistory::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function sornHistories()
    {
        return $this->hasMany(CarSornHistory::class)->orderByDesc('sorn_started_at')->orderByDesc('id');
    }

    public function phvlSuspensionHistories()
    {
        return $this->hasMany(CarPhvlSuspensionHistory::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function phvlSuspensionStatusLabel(): string
    {
        return app(\App\Services\PhvlSuspensionService::class)->effectiveStatus($this) === \App\Services\PhvlSuspensionService::STATUS_ACTIVE
            ? 'Active (not suspended)'
            : (\App\Services\PhvlSuspensionService::statusLabels()[app(\App\Services\PhvlSuspensionService::class)->effectiveStatus($this)] ?? '—');
    }

    public function hasPhvlLicenceRevoked(): bool
    {
        return app(\App\Services\PhvlSuspensionService::class)->effectiveStatus($this)
            === \App\Services\PhvlSuspensionService::STATUS_LICENCE_REVOKED;
    }

    public function scopeNonFaultDamaged($query)
    {
        return app(\App\Services\PhvlSuspensionService::class)->scopeNonFaultDamagedCars($query);
    }

    /**
     * @return list<string>
     */
    public function oldLogBookFileNames(): array
    {
        $names = $this->old_log_book;

        return array_values(array_filter(
            is_array($names) ? $names : [],
            fn ($n) => is_string($n) && $n !== ''
        ));
    }

    /**
     * @return list<string>
     */
    public function v5DocumentFileNames(): array
    {
        $names = $this->v5_document;

        return array_values(array_filter(
            is_array($names) ? $names : [],
            fn ($n) => is_string($n) && $n !== ''
        ));
    }

    /**
     * @return array<string, string>
     */
    public static function fleetStatusLabels(): array
    {
        return [
            self::FLEET_STATUS_PREPARATION_FOR_PHVL => 'PHVL Preparation',
            self::FLEET_STATUS_AVAILABLE_FOR_RENT => 'Available for Rent',
            self::FLEET_STATUS_NON_COMPLIANT => 'Non-Compliant',
            'reserved' => 'Reserved',
            'vehicle_swap' => 'Vehicle Swap',
            'damaged' => 'Damaged',
            'written_off' => 'Written Off',
            'stolen' => 'Stolen',
            'for_sale' => 'For Sale',
            'sold' => 'Sold',
            'sorn' => 'SORN',
        ];
    }

    public function fleetStatusLabel(): string
    {
        $status = $this->fleet_status ?? self::FLEET_STATUS_AVAILABLE_FOR_RENT;

        return self::fleetStatusLabels()[$status]
            ?? ucwords(str_replace('_', ' ', $status));
    }

    /**
     * @return list<string>
     */
    public static function fleetStatusesLockedForEditing(): array
    {
        return [
            self::FLEET_STATUS_WRITTEN_OFF,
            self::FLEET_STATUS_STOLEN,
            self::FLEET_STATUS_SOLD,
        ];
    }

    public function isFleetStatusLockedForEditing(): bool
    {
        return in_array($this->fleet_status, self::fleetStatusesLockedForEditing(), true);
    }

    /**
     * @return list<string>
     */
    public static function fleetStatusesExcludedFromPhvlManagement(): array
    {
        return [
            self::FLEET_STATUS_WRITTEN_OFF,
            self::FLEET_STATUS_STOLEN,
            self::FLEET_STATUS_SOLD,
            'for_sale',
        ];
    }

    public function isExcludedFromPhvlManagement(): bool
    {
        return in_array($this->fleet_status, self::fleetStatusesExcludedFromPhvlManagement(), true);
    }

    // ==================== SCOPES ====================

    // ✅ Scope for specific tenant
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ✅ Scope for current user's tenant
    public function scopeForCurrentTenant($query)
    {
        $tenant = auth()->user()->currentTenant();

        return $query->where('tenant_id', $tenant->id ?? 0);
    }

    public function scopeEligibleForPhvlManagement($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('fleet_status')
                ->orWhereNotIn('fleet_status', self::fleetStatusesExcludedFromPhvlManagement());
        });
    }

    /**
     * Council name from the PHV row with the latest expiry date (current or most recent).
     */
    public function latestPhvCounselName(): ?string
    {
        $phv = $this->phvs
            ->sortByDesc(fn (CarPhv $p) => optional($p->expiry_date)->timestamp ?? 0)
            ->first();

        return $phv?->counsel?->name;
    }

    /**
     * Latest-by-expiry policy when it is Active and not past expiry; otherwise null.
     */
    public function currentActiveInsurance(): ?CarInsurance
    {
        $insurance = $this->insurances
            ->sortByDesc(fn (CarInsurance $i) => [optional($i->expiry_date)->timestamp ?? 0, $i->id])
            ->first();

        if (! $insurance?->status || ! $insurance->expiry_date) {
            return null;
        }

        if (strcasecmp($insurance->status->name, 'Active') !== 0) {
            return null;
        }

        if ($insurance->expiry_date->copy()->startOfDay()->lt(now()->startOfDay())) {
            return null;
        }

        return $insurance;
    }

    /**
     * Insurance is shown as Active when the latest-by-expiry policy has status "Active" and is not past its expiry date.
     */
    public function isInsuranceCurrentlyActive(): bool
    {
        return $this->currentActiveInsurance() !== null;
    }

    public function latestMot(): ?CarMot
    {
        return $this->mots
            ->sortByDesc(fn (CarMot $m) => [optional($m->expiry_date)->timestamp ?? 0, $m->id])
            ->first();
    }

    public function latestRoadTax(): ?CarRoadTax
    {
        return $this->roadTaxes
            ->sortByDesc(fn (CarRoadTax $r) => [optional($r->expiryDate())->timestamp ?? 0, $r->id])
            ->first();
    }

    public function isMotCurrentlyValid(): bool
    {
        $mot = $this->latestMot();

        return (bool) ($mot?->expiry_date && $mot->expiry_date->copy()->startOfDay()->gte(now()->startOfDay()));
    }

    public function isRoadTaxCurrentlyValid(): bool
    {
        $roadTax = $this->latestRoadTax();
        $expiry = $roadTax?->expiryDate();

        return (bool) ($expiry && $expiry->copy()->startOfDay()->gte(now()->startOfDay()));
    }

    public function isRoadLegalCompliant(): bool
    {
        return $this->isMotCurrentlyValid()
            && $this->isRoadTaxCurrentlyValid()
            && $this->isPhvCurrentlyActive();
    }

    /**
     * @return list<string>
     */
    public function complianceFailureReasons(): array
    {
        $reasons = [];

        if (! $this->isMotCurrentlyValid()) {
            $reasons[] = 'mot';
        }

        if (! $this->isRoadTaxCurrentlyValid()) {
            $reasons[] = 'road_tax';
        }

        if (! $this->isPhvCurrentlyActive()) {
            $reasons[] = $this->hasPhvRecord() ? 'phv' : 'phv_missing';
        }

        return $reasons;
    }

    public function isEligibleForAgreementSelection(): bool
    {
        if ($this->sorn_applied || $this->hasActiveReservation()) {
            return false;
        }

        if ($this->hasPhvlLicenceRevoked()) {
            return false;
        }

        if (in_array(
            $this->fleet_status ?? self::FLEET_STATUS_AVAILABLE_FOR_RENT,
            self::fleetStatusesBlockedForAgreementSelection(),
            true
        )) {
            return false;
        }

        return $this->isRoadLegalCompliant();
    }

    /**
     * @return list<string>
     */
    public static function fleetStatusesBlockedForAgreementSelection(): array
    {
        return [
            self::FLEET_STATUS_PREPARATION_FOR_PHVL,
            self::FLEET_STATUS_NON_COMPLIANT,
            'damaged',
            'written_off',
            'stolen',
            'for_sale',
            'sold',
            'reserved',
            'vehicle_swap',
            'sorn',
        ];
    }

    public function hasActiveReservation(): bool
    {
        if ($this->relationLoaded('reservations')) {
            return $this->activeReservation() !== null;
        }

        return $this->reservations()->where('status', 'active')->exists();
    }

    public function isSelectableForAgreement(array $rentedCarIds, ?CarReservation $convertingReservation = null): bool
    {
        if ($convertingReservation !== null && $this->matchesReservationForAgreementConversion($convertingReservation)) {
            return ! in_array($this->id, $rentedCarIds, true);
        }

        if (in_array($this->id, $rentedCarIds, true)) {
            return false;
        }

        return $this->isEligibleForAgreementSelection();
    }

    public function matchesReservationForAgreementConversion(CarReservation $reservation): bool
    {
        if ($reservation->trashed()) {
            return false;
        }

        $reservationCarId = (int) $reservation->car_id;

        if ($reservationCarId > 0 && $reservationCarId !== (int) $this->id) {
            return false;
        }

        return strtolower((string) ($reservation->status ?? 'active')) === 'active';
    }

    public function latestService()
    {
        return $this->services
            ->sortByDesc(fn (CarService $service) => [optional($service->service_date)->timestamp ?? 0, $service->id])
            ->first();
    }

    public function nextServiceDueDate()
    {
        $latest = $this->latestService();

        return $latest?->service_date?->copy()->addMonths(3);
    }

    public function activeReservation()
    {
        return $this->reservations
            ->where('status', 'active')
            ->sortByDesc(fn (CarReservation $reservation) => [optional($reservation->reservation_date)->timestamp ?? 0, $reservation->id])
            ->first();
    }

    public function isPhvCurrentlyActive(): bool
    {
        $phv = $this->phvs
            ->sortByDesc(fn (CarPhv $p) => [optional($p->expiry_date)->timestamp ?? 0, $p->id])
            ->first();

        return (bool) ($phv?->expiry_date && $phv->expiry_date->copy()->startOfDay()->gte(now()->startOfDay()));
    }

    public function hasPhvRecord(): bool
    {
        return $this->phvs->isNotEmpty();
    }

    public function isAvailableForRent(): bool
    {
        if ($this->sorn_applied || $this->activeReservation()) {
            return false;
        }

        if (in_array(
            $this->fleet_status ?? self::FLEET_STATUS_AVAILABLE_FOR_RENT,
            self::fleetStatusesBlockedForAgreementSelection(),
            true
        )) {
            return false;
        }

        if ($this->available_from_date && $this->available_from_date->copy()->startOfDay()->gt(now()->startOfDay())) {
            return false;
        }

        $hasActiveAgreement = $this->agreements
            ->filter(function (Agreement $agreement) {
                return $agreement->start_date?->copy()->startOfDay()->lte(now()->startOfDay())
                    && $agreement->end_date?->copy()->startOfDay()->gte(now()->startOfDay())
                    && ! $agreement->termination_notice_date;
            })
            ->isNotEmpty();

        return ! $hasActiveAgreement && $this->isInsuranceCurrentlyActive() && $this->isPhvCurrentlyActive();
    }
}
