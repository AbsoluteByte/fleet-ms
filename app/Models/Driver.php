<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','first_name', 'middle_name', 'last_name', 'dob', 'email',
        'phone_number', 'ni_number', 'address1', 'address2', 'post_code', 'town',
        'county', 'country_id', 'driver_license_number',
        'driver_license_expiry_date', 'phd_license_number',
        'phd_license_expiry_date', 'next_of_kin', 'next_of_kin_phone',
        'driver_license_document', 'driver_phd_license_document',
        'phd_card_document', 'dvla_license_summary', 'misc_document',
        'proof_of_address_document', 'is_invited', 'invited_at',
        'invitation_token', 'invitation_accepted_at', 'user_id', 'createdBy', 'updatedBy'
    ];

    protected $casts = [
        'dob' => 'date',
        'driver_license_expiry_date' => 'date',
        'phd_license_expiry_date' => 'date',
        'invited_at' => 'datetime',
        'invitation_accepted_at' => 'datetime',
        'is_invited' => 'boolean'
    ];
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function agreements()
    {
        return $this->hasMany(Agreement::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name);
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
        if (!$this->invitation_token) {
            return null;
        }

        return route('driver.accept-invitation', $this->invitation_token);
    }

    public function isInvitationExpired()
    {
        if (!$this->invited_at) {
            return false;
        }

        // Invitation expires after 7 days
        return $this->invited_at->addDays(7)->isPast();
    }

    public function canBeInvited()
    {
        return !$this->is_invited || $this->isInvitationExpired();
    }

    public function hasAcceptedInvitation()
    {
        return !is_null($this->invitation_accepted_at);
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

    // Get overdue payments
    public function overduePayments()
    {
        return $this->collections()
            ->where('payment_status', 'overdue')
            ->with(['agreement.car']);
    }
}
