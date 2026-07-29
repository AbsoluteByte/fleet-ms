<?php

namespace App\Models;

use App\Services\AgreementInvoiceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Agreement extends Model
{
    use HasFactory;

    public const PDF_END_TIME = '11:00';

    protected $fillable = [
        'tenant_id', 'company_id', 'start_date', 'end_date', 'billing_anchor_date', 'driver_id', 'paying_company_name',
        'car_id', 'agreed_rent', 'rent_interval', 'insurance_type',
        'deposit_amount', 'discount_type', 'discount_value', 'discount_notes',
        'discount_is_one_time', 'discount_started_at', 'discount_consumed_at', 'discount_consumed_invoice_id',
        'security_deposit', 'mileage_out', 'mileage_in',
        'collection_type', 'auto_schedule_collections', 'next_collection_date',
        'condition_report', 'notes', 'status_id', 'parent_agreement_id', 'upgraded_from_agreement_id',
        'swap_reason', 'swap_phvl_issue_type', 'swap_phvl_issue_notes', 'swap_reason_notes',
        // New insurance fields
        'using_own_insurance', 'insurance_provider_id',
        'own_insurance_provider_name', 'own_insurance_start_date',
        'own_insurance_end_date', 'own_insurance_type',
        'own_insurance_policy_number', 'own_insurance_proof_document', 'mutual_detail_slip_document', 'createdBy', 'updatedBy',

        'hellosign_request_id',
        'hellosign_sign_url',
        'hellosign_status',
        'esign_sent_at',
        'esign_completed_at',
        'esign_document_path',
        'termination_notice_date',
        'termination_available_from_date',
        'termination_notes',
        'termination_recorded_by',
        'closing_date',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'date',
        'billing_anchor_date' => 'date',
        'next_collection_date' => 'date',
        'agreed_rent' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_is_one_time' => 'boolean',
        'discount_started_at' => 'datetime',
        'discount_consumed_at' => 'datetime',
        'discount_consumed_invoice_id' => 'integer',
        'security_deposit' => 'decimal:2',
        'auto_schedule_collections' => 'boolean',

        'using_own_insurance' => 'boolean',
        'own_insurance_proof_document' => 'array',
        'mutual_detail_slip_document' => 'array',
        'own_insurance_start_date' => 'date',
        'own_insurance_end_date' => 'date',

        // New e-signature casts
        'esign_sent_at' => 'datetime',
        'esign_completed_at' => 'datetime',
        'termination_notice_date' => 'date',
        'termination_available_from_date' => 'date',
        'closing_date' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function depositRefund()
    {
        return $this->hasOne(DepositRefund::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'source_id')
            ->whereIn('invoice_type', ['agreement', 'agreement_deposit', 'agreement_additional_charge']);
    }

    public function discountConsumedInvoice()
    {
        return $this->belongsTo(Invoice::class, 'discount_consumed_invoice_id');
    }

    public function hasConfiguredDiscount(): bool
    {
        return in_array($this->discount_type, ['percentage', 'fixed'], true)
            && (float) $this->discount_value > 0;
    }

    public function hasPendingOneTimeDiscount(): bool
    {
        return $this->hasConfiguredDiscount()
            && (bool) $this->discount_is_one_time
            && $this->discount_consumed_invoice_id === null;
    }

    public function discountAmountFor(float $subtotal): float
    {
        $subtotal = round(max($subtotal, 0), 2);
        if (! $this->hasConfiguredDiscount() || $subtotal <= 0) {
            return 0;
        }

        if ($this->discount_type === 'percentage') {
            return round(min($subtotal * ((float) $this->discount_value / 100), $subtotal), 2);
        }

        return round(min((float) $this->discount_value, $subtotal), 2);
    }

    public function getDiscountedRentAttribute(): float
    {
        $rent = round((float) $this->agreed_rent, 2);

        return round(max($rent - $this->discountAmountFor($rent), 0), 2);
    }

    public function deductions()
    {
        return $this->hasMany(AgreementDeduction::class)->orderBy('sort_order')->orderBy('id');
    }

    public function additionalCharges()
    {
        return $this->hasMany(AgreementAdditionalCharge::class)->orderBy('sort_order')->orderBy('id');
    }

    public function getDeductionsTotalAttribute(): float
    {
        if ($this->relationLoaded('deductions')) {
            return round((float) $this->deductions->sum('amount'), 2);
        }

        return round((float) $this->deductions()->sum('amount'), 2);
    }

    public function isClosedForDepositRefund(): bool
    {
        $name = strtolower((string) optional($this->status)->name);

        return in_array($name, ['expired', 'terminated'], true);
    }

    public function canRequestDepositRefund(): bool
    {
        return $this->isClosedForDepositRefund()
            && (float) $this->deposit_amount > 0
            && ! $this->hasBeenUpgraded()
            && ! $this->depositRefund()->exists();
    }

    /**
     * @return null|'pending'|'posted'
     */
    public function depositRefundStatus(): ?string
    {
        $refund = $this->relationLoaded('depositRefund')
            ? $this->depositRefund
            : $this->depositRefund()->first();

        if (! $refund) {
            return null;
        }

        return $refund->isPosted()
            ? DepositRefund::POSTING_STATUS_POSTED
            : DepositRefund::POSTING_STATUS_PENDING;
    }

    public function parentAgreement()
    {
        return $this->belongsTo(self::class, 'parent_agreement_id');
    }

    public function replacementVehicleAgreements()
    {
        return $this->hasMany(self::class, 'parent_agreement_id');
    }

    public function upgradedFromAgreement()
    {
        return $this->belongsTo(self::class, 'upgraded_from_agreement_id');
    }

    public function upgradedToAgreement()
    {
        return $this->hasOne(self::class, 'upgraded_from_agreement_id');
    }

    public function isUpgradedAgreement(): bool
    {
        return $this->upgraded_from_agreement_id !== null;
    }

    public function hasBeenUpgraded(): bool
    {
        return $this->upgradedToAgreement()->exists();
    }

    public function isReplacementVehicle(): bool
    {
        return strcasecmp((string) optional($this->status)->name, 'Replacement Vehicle') === 0;
    }

    public function isSwapAgreement(): bool
    {
        return strcasecmp((string) optional($this->status)->name, 'Swap') === 0;
    }

    public function isBillableStatus(): bool
    {
        $name = strtolower(trim((string) optional($this->status)->name));

        return in_array($name, ['active', 'swap'], true);
    }

    public function effectiveCloseDate(): ?Carbon
    {
        if ($this->closing_date) {
            return $this->closing_date->copy();
        }

        return $this->end_date?->copy();
    }

    public function previousVehicleRegistration(): ?string
    {
        if ($this->isReplacementVehicle()) {
            $registration = $this->parentAgreement?->car?->registration;

            return $registration ? (string) $registration : null;
        }

        if ($this->isUpgradedAgreement()) {
            $registration = $this->upgradedFromAgreement?->car?->registration;

            return $registration ? (string) $registration : null;
        }

        return null;
    }

    public function vehicleRegistrationsIncludingReplacements(): Collection
    {
        $registrations = collect();

        if ($registration = $this->car?->registration) {
            $registrations->push($registration);
        }

        $replacements = $this->relationLoaded('replacementVehicleAgreements')
            ? $this->replacementVehicleAgreements
            : $this->replacementVehicleAgreements()->currentlyActiveReplacement()->with('car')->get();

        foreach ($replacements as $replacement) {
            if ($registration = $replacement->car?->registration) {
                $registrations->push($registration);
            }
        }

        return $registrations->filter()->unique()->values();
    }

    public function vehicleRegistrationsLabel(string $emptyLabel = '—'): string
    {
        $registrations = $this->vehicleRegistrationsIncludingReplacements();

        return $registrations->isNotEmpty() ? $registrations->implode(', ') : $emptyLabel;
    }

    public function documentCompany(): ?Company
    {
        return $this->car?->company ?? $this->company;
    }

    /**
     * When the driver assignment on this car ends for ticket-tracking / historical lookups.
     */
    public function effectiveAssignmentEndAt(): Carbon
    {
        $candidates = [];

        $upgradedTo = $this->relationLoaded('upgradedToAgreement')
            ? $this->upgradedToAgreement
            : $this->upgradedToAgreement()->first();

        if ($upgradedTo?->start_date) {
            $candidates[] = $upgradedTo->start_date->copy();
        }

        if ($this->closing_date) {
            $candidates[] = $this->closing_date->copy();
        }

        if ($this->end_date) {
            $candidates[] = $this->end_date->copy()->setTimeFromTimeString(self::PDF_END_TIME);
        }

        return collect($candidates)->sort()->first() ?? now();
    }

    public function isAssignedAt(Carbon $at): bool
    {
        if (! $this->start_date) {
            return false;
        }

        return $this->start_date->lte($at) && $at->lt($this->effectiveAssignmentEndAt());
    }

    public function billingAnchorDate(): Carbon
    {
        return ($this->billing_anchor_date ?? $this->start_date)->copy()->startOfDay();
    }

    public function hasDeferredBillingAnchor(): bool
    {
        if (! $this->billing_anchor_date || ! $this->start_date) {
            return false;
        }

        return $this->billing_anchor_date->copy()->startOfDay()
            ->gt($this->start_date->copy()->startOfDay());
    }

    public function scopeBillable($query)
    {
        return $query->whereHas('status', function ($statusQuery) {
            $statusQuery->whereIn('name', ['Active', 'Swap']);
        });
    }

    public function scopeEligibleAsOriginal($query)
    {
        $today = now()->startOfDay();

        return $query
            ->whereNull('parent_agreement_id')
            ->whereNull('upgraded_from_agreement_id')
            ->whereDoesntHave('upgradedToAgreement')
            ->whereHas('status', fn ($statusQuery) => $statusQuery->where('name', 'Active'))
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    public function scopeCurrentlyActive($query)
    {
        $today = now()->startOfDay();

        return $query
            ->whereHas('status', fn ($statusQuery) => $statusQuery->whereIn('name', ['Active', 'Swap']))
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    public function scopeCurrentlyActiveReplacement($query)
    {
        $today = now()->startOfDay();

        return $query
            ->whereHas('status', fn ($statusQuery) => $statusQuery->where('name', 'Replacement Vehicle'))
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    /**
     * Active/Swap-status agreements assigned to a vehicle, including future start dates.
     */
    public function scopeWithActiveAssignment($query)
    {
        $today = now()->startOfDay();

        return $query
            ->whereHas('status', fn ($statusQuery) => $statusQuery->whereIn('name', ['Active', 'Swap']))
            ->whereNull('termination_notice_date')
            ->whereDate('end_date', '>=', $today);
    }

    /**
     * Active, Swap, or Replacement Vehicle agreements assigned to a vehicle, including future start dates.
     */
    public function scopeWithRentAssignment($query)
    {
        $today = now()->startOfDay();

        return $query
            ->whereHas('status', fn ($statusQuery) => $statusQuery->whereIn('name', ['Active', 'Swap', 'Replacement Vehicle']))
            ->whereNull('termination_notice_date')
            ->whereDate('end_date', '>=', $today);
    }

    /**
     * @return list<int>
     */
    public static function rentedCarIdsForTenant(int $tenantId, ?int $excludeAgreementId = null): array
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->withActiveAssignment()
            ->when($excludeAgreementId, fn ($query) => $query->where('id', '!=', $excludeAgreementId))
            ->pluck('car_id')
            ->unique()
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    public static function rentAssignedCarIdsForTenant(int $tenantId, ?int $excludeAgreementId = null): array
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->withRentAssignment()
            ->when($excludeAgreementId, fn ($query) => $query->where('id', '!=', $excludeAgreementId))
            ->pluck('car_id')
            ->unique()
            ->filter()
            ->values()
            ->all();
    }

    public static function activeAgreementForCar(int $tenantId, int $carId): ?self
    {
        return static::query()
            ->where('tenant_id', $tenantId)
            ->where('car_id', $carId)
            ->withActiveAssignment()
            ->first();
    }

    public function swapReasonLabel(): ?string
    {
        if (! $this->swap_reason) {
            return null;
        }

        return VehicleSwap::reasonLabels()[$this->swap_reason] ?? $this->swap_reason;
    }

    public function terminationRecordedBy()
    {
        return $this->belongsTo(User::class, 'termination_recorded_by');
    }

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    /**
     * @return list<string>
     */
    public function ownInsuranceProofFileNames(): array
    {
        $names = $this->own_insurance_proof_document;

        if (is_string($names) && $names !== '') {
            return [$names];
        }

        return array_values(array_filter(
            is_array($names) ? $names : [],
            fn ($n) => is_string($n) && $n !== ''
        ));
    }

    /**
     * @return list<string>
     */
    public function mutualDetailSlipFileNames(): array
    {
        $names = $this->mutual_detail_slip_document;

        if (is_string($names) && $names !== '') {
            return [$names];
        }

        return array_values(array_filter(
            is_array($names) ? $names : [],
            fn ($n) => is_string($n) && $n !== ''
        ));
    }

    public function collections()
    {
        return $this->hasMany(AgreementCollection::class);
    }

    public function penalties()
    {
        return $this->hasMany(Penalty::class);
    }

    // New methods for enhanced functionality
    public function pendingCollections()
    {
        return $this->collections()->where('payment_status', 'pending');
    }

    public function overdueCollections()
    {
        return $this->collections()->where('payment_status', 'overdue');
    }

    public function generateCollections()
    {
        if (! $this->auto_schedule_collections) {
            return;
        }

        $startDate = $this->start_date;
        $endDate = $this->end_date;
        $collectionType = $this->collection_type;
        $invoiceService = app(AgreementInvoiceService::class);

        // Clear existing auto-generated collections
        $this->collections()->where('is_auto_generated', true)->delete();

        $collectionNumber = 1;

        if ($this->hasDeferredBillingAnchor()) {
            $periodStart = $this->start_date->copy()->startOfDay();
            $anchor = $this->billing_anchor_date->copy()->startOfDay();
            $proratedAmount = $invoiceService->calculateInitialProrationAmount($this, $periodStart, $anchor);

            if ($proratedAmount > 0) {
                $this->collections()->create([
                    'date' => $this->start_date,
                    'due_date' => $this->start_date,
                    'method' => 'auto_scheduled',
                    'amount' => $proratedAmount,
                    'payment_status' => 'pending',
                    'is_auto_generated' => true,
                    'notes' => "Auto-generated collection #$collectionNumber (initial proration until {$anchor->toDateString()})",
                ]);
                $collectionNumber++;
            }

            $currentDate = $anchor->copy();
        } else {
            $currentDate = $startDate->copy();
        }

        while ($currentDate <= $endDate) {
            $dueDate = $currentDate->copy();

            // Calculate next collection date based on type
            switch ($collectionType) {
                case 'weekly':
                    $nextDate = $currentDate->copy()->addWeek();
                    break;
                case 'monthly':
                    $nextDate = $currentDate->copy()->addMonth();
                    break;
                case 'static':
                    $nextDate = $endDate->copy()->addDay();
                    break;
            }

            $this->collections()->create([
                'date' => $currentDate,
                'due_date' => $dueDate,
                'method' => 'auto_scheduled',
                'amount' => $this->agreed_rent,
                'payment_status' => 'pending',
                'is_auto_generated' => true,
                'notes' => "Auto-generated collection #$collectionNumber",
            ]);

            $currentDate = $nextDate;
            $collectionNumber++;

            if ($collectionType === 'static') {
                break;
            }
        }

        $this->update([
            'next_collection_date' => $this->collections()
                ->where('payment_status', 'pending')
                ->orderBy('due_date')
                ->first()?->due_date,
        ]);
    }

    public function updateOverdueCollections()
    {
        $this->collections()
            ->where('payment_status', 'pending')
            ->where('due_date', '<', now())
            ->update(['payment_status' => 'overdue']);
    }

    public function getTotalOutstandingAttribute()
    {
        if ($this->invoices()->exists()) {
            return round((float) $this->invoices()->sum('balance_amount'), 2);
        }

        return $this->collections()
            ->whereIn('payment_status', ['pending', 'overdue'])
            ->sum('amount');
    }

    public function getTotalPaidAttribute()
    {
        if ($this->invoices()->exists()) {
            return round((float) $this->invoices()->sum('paid_amount'), 2);
        }

        return $this->collections()
            ->where('payment_status', 'paid')
            ->sum('amount_paid');
    }

    /**
     * Check if can send for e-signature
     */
    public function canSendForESignature()
    {
        return ! $this->hellosign_request_id &&
            $this->driver &&
            $this->driver->email &&
            ! empty($this->driver->email);
    }

    /**
     * Get e-signature status badge class
     */
    public function getEsignStatusBadgeAttribute()
    {
        switch ($this->hellosign_status) {
            case 'pending':
                return 'badge bg-warning';
            case 'signed':
                return 'badge bg-success';
            case 'declined':
                return 'badge bg-danger';
            case 'cancelled':
                return 'badge bg-secondary';
            default:
                return 'badge bg-light text-dark';
        }
    }

    /**
     * Get e-signature status text
     */
    public function getEsignStatusTextAttribute()
    {
        if (! $this->hellosign_status) {
            return 'Not Sent';
        }

        return ucfirst($this->hellosign_status);
    }

    /**
     * Get signed document URL
     */
    public function getSignedDocumentUrlAttribute()
    {
        if ($this->esign_document_path && file_exists(public_path($this->esign_document_path))) {
            return asset($this->esign_document_path);
        }

        return null;
    }

    public function signatureTokens()
    {
        return $this->hasMany(AgreementSignatureToken::class);
    }

    /**
     * Get tenant's settings
     */
    public function getSettings()
    {
        return Setting::getForTenant($this->tenant_id);
    }

    /**
     * Get active signature token (for custom signing)
     */
    public function getActiveSignatureToken()
    {
        return $this->signatureTokens()
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Get latest signature token
     */
    public function getLatestSignatureToken()
    {
        return $this->signatureTokens()->latest()->first();
    }
}
