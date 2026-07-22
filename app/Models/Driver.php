<?php

namespace App\Models;

use App\Services\DriverPersistenceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'first_name', 'middle_name', 'last_name', 'dob', 'email',
        'phone_number', 'ni_number', 'address1', 'address2', 'post_code', 'town',
        'county', 'country_id', 'driver_license_number',
        'driver_license_expiry_date', 'phd_license_number',
        'phd_license_expiry_date', 'next_of_kin', 'next_of_kin_phone',
        'driver_license_document', 'driver_phd_license_document',
        'phd_card_document', 'dvla_license_summary', 'misc_document',
        'proof_of_address_document', 'is_invited', 'invited_at',
        'invitation_token', 'invitation_accepted_at', 'user_id', 'is_active',
        'payment_follow_up_notes', 'payment_remind_at', 'payment_reminder_dismissed_at',
        'createdBy', 'updatedBy',
    ];

    protected $casts = [
        'dob' => 'date',
        'driver_license_expiry_date' => 'date',
        'phd_license_expiry_date' => 'date',
        'invited_at' => 'datetime',
        'invitation_accepted_at' => 'datetime',
        'is_invited' => 'boolean',
        'is_active' => 'boolean',
        'payment_remind_at' => 'datetime',
        'payment_reminder_dismissed_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isInactive(): bool
    {
        return ! $this->is_active;
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function agreements()
    {
        return $this->hasMany(Agreement::class);
    }

    public function latestAgreement(): ?Agreement
    {
        return $this->agreements()
            ->with('car')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(DriverCreditTransaction::class);
    }

    public function reservations()
    {
        return $this->hasMany(CarReservation::class);
    }

    public function documentArchives()
    {
        return $this->hasMany(DriverDocumentArchive::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name.' '.$this->middle_name.' '.$this->last_name);
    }

    /**
     * Label for driver dropdowns: "John Smith (SW1A 1AA)"
     */
    public function selectOptionLabel(): string
    {
        $name = trim($this->full_name);
        $postCode = trim((string) ($this->post_code ?? ''));

        if ($name === '' && $postCode === '') {
            return 'Driver #'.$this->id;
        }

        if ($postCode === '') {
            return $name;
        }

        return $name !== '' ? "{$name} ({$postCode})" : "({$postCode})";
    }

    /**
     * @param  array<int, mixed>  $lines
     */
    public static function formatCommaSeparatedAddress(array $lines): string
    {
        $formatted = collect($lines)
            ->map(function ($line) {
                if (! is_scalar($line)) {
                    return null;
                }

                $trimmed = trim((string) $line);

                return $trimmed === '' ? null : $trimmed;
            })
            ->filter()
            ->implode(', ');

        return preg_replace('/,\s*,+/', ', ', $formatted) ?? $formatted;
    }

    public function commaSeparatedAddress(): string
    {
        return static::formatCommaSeparatedAddress([
            $this->address1,
            $this->address2,
            $this->town,
            $this->post_code,
        ]);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function generateInvitationToken()
    {
        $this->invitation_token = Str::random(64);
        $this->save();

        return $this->invitation_token;
    }

    public function getInvitationUrlAttribute()
    {
        if (! $this->invitation_token) {
            return null;
        }

        return route('driver.accept-invitation', $this->invitation_token);
    }

    public function isInvitationExpired()
    {
        if (! $this->invited_at) {
            return false;
        }

        // Invitation expires after 7 days
        return $this->invited_at->addDays(7)->isPast();
    }

    public function canBeInvited()
    {
        return ! $this->is_invited || $this->isInvitationExpired();
    }

    public function hasAcceptedInvitation()
    {
        return ! is_null($this->invitation_accepted_at);
    }

    // Get driver's active agreements
    public function activeAgreements()
    {
        return $this->agreements()
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->with(['car', 'company', 'status']);
    }

    // Get driver's collections (payments)
    public function collections()
    {
        return $this->hasManyThrough(
            AgreementCollection::class,
            Agreement::class,
            'driver_id',
            'agreement_id'
        );
    }

    // Get pending payments
    public function pendingPayments()
    {
        return $this->collections()
            ->where('payment_status', 'pending')
            ->with(['agreement.car']);
    }

    public function activeInvoices()
    {
        return $this->invoices()
            ->where('balance_amount', '>', 0)
            ->where('status', '!=', 'cancelled');
    }

    public function overdueInvoices()
    {
        return $this->activeInvoices()->whereDate('due_date', '<', now());
    }

    public function getTotalInvoicedAttribute()
    {
        return (float) $this->invoices()->sum('total_amount');
    }

    public function getTotalPaidAttribute()
    {
        return (float) $this->payments()->posted()->sum('amount');
    }

    public function getTotalAllocatedAttribute()
    {
        return (float) PaymentAllocation::whereHas('payment', function ($query) {
            $query->where('driver_id', $this->id)->posted();
        })->sum('allocated_amount');
    }

    public function getTotalDueAttribute()
    {
        return (float) $this->activeInvoices()->sum('balance_amount');
    }

    public function getCreditAmountAttribute()
    {
        $refundedCredit = Schema::hasTable('driver_credit_transaction_lines')
            ? DriverCreditTransactionLine::query()
                ->whereHas('transaction', function ($query) {
                    $query->where('driver_id', $this->id)
                        ->where('kind', DriverCreditTransaction::KIND_REFUND)
                        ->where('posting_status', DriverCreditTransaction::STATUS_POSTED);
                })
                ->where('status', DriverCreditTransactionLine::STATUS_CONSUMED)
                ->sum('amount')
            : 0;

        return round(max($this->total_paid - $this->total_allocated - (float) $refundedCredit, 0), 2);
    }

    public function getReservedCreditAmountAttribute(): float
    {
        if (! Schema::hasTable('driver_credit_transaction_lines')) {
            return 0.0;
        }

        return round((float) DriverCreditTransactionLine::query()
            ->whereHas('transaction', function ($query) {
                $query->where('driver_id', $this->id)
                    ->where('posting_status', DriverCreditTransaction::STATUS_PENDING);
            })
            ->where('status', DriverCreditTransactionLine::STATUS_RESERVED)
            ->sum('amount'), 2);
    }

    public function getAvailableCreditAmountAttribute(): float
    {
        return round(max($this->credit_amount - $this->reserved_credit_amount, 0), 2);
    }

    // Get overdue payments
    public function overduePayments()
    {
        return $this->collections()
            ->where('payment_status', 'overdue')
            ->with(['agreement.car']);
    }

    public function isProfileCompleteForAgreement(): bool
    {
        return app(DriverPersistenceService::class)->isProfileCompleteForAgreement($this);
    }

    /**
     * @return list<string>
     */
    public function missingProfileFieldLabels(): array
    {
        return app(DriverPersistenceService::class)->missingProfileFieldLabels($this);
    }

    public function hasPaymentFollowUpNote(): bool
    {
        return filled($this->payment_follow_up_notes);
    }

    public function hasPaymentReminder(): bool
    {
        return $this->payment_remind_at !== null;
    }

    public function isPaymentReminderDue(): bool
    {
        if (! $this->payment_remind_at || $this->payment_remind_at->gt(now())) {
            return false;
        }

        return $this->payment_reminder_dismissed_at === null
            || $this->payment_reminder_dismissed_at->lt($this->payment_remind_at);
    }

    public function scopeWithDuePaymentReminder($query)
    {
        return $query
            ->whereNotNull('payment_remind_at')
            ->where('payment_remind_at', '<=', now())
            ->where(function ($inner) {
                $inner->whereNull('payment_reminder_dismissed_at')
                    ->orWhereColumn('payment_reminder_dismissed_at', '<', 'payment_remind_at');
            });
    }
}
