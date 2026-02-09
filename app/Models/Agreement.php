<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Agreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'company_id', 'start_date', 'end_date', 'driver_id',
        'car_id', 'agreed_rent', 'rent_interval', 'insurance_type',
        'deposit_amount', 'security_deposit', 'mileage_out', 'mileage_in',
        'collection_type', 'auto_schedule_collections', 'next_collection_date',
        'condition_report', 'notes', 'status_id',
        // New insurance fields
        'using_own_insurance', 'insurance_provider_id',
        'own_insurance_provider_name', 'own_insurance_start_date',
        'own_insurance_end_date', 'own_insurance_type',
        'own_insurance_policy_number', 'own_insurance_proof_document', 'createdBy', 'updatedBy',

        'hellosign_request_id',
        'hellosign_sign_url',
        'hellosign_status',
        'hellosign_document_path',
        'esign_sent_at',
        'esign_completed_at',
        'esign_document_path'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_collection_date' => 'date',
        'agreed_rent' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'security_deposit' => 'decimal:2',
        'auto_schedule_collections' => 'boolean',

        'using_own_insurance' => 'boolean',
        'own_insurance_start_date' => 'date',
        'own_insurance_end_date' => 'date',

        // New e-signature casts
        'esign_sent_at' => 'datetime',
        'esign_completed_at' => 'datetime',
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

    public function insuranceProvider()
    {
        return $this->belongsTo(InsuranceProvider::class);
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
        if (!$this->auto_schedule_collections) {
            return;
        }

        $startDate = $this->start_date;
        $endDate = $this->end_date;
        $collectionType = $this->collection_type;

        // Clear existing auto-generated collections
        $this->collections()->where('is_auto_generated', true)->delete();

        $currentDate = $startDate->copy();
        $collectionNumber = 1;

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
                'notes' => "Auto-generated collection #$collectionNumber"
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
                ->first()?->due_date
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
        return $this->collections()
            ->whereIn('payment_status', ['pending', 'overdue'])
            ->sum('amount');
    }

    public function getTotalPaidAttribute()
    {
        return $this->collections()
            ->where('payment_status', 'paid')
            ->sum('amount_paid');
    }

    /**
     * Check if agreement can be sent for e-signature
     */
    public function canSendForESignature()
    {
        return !$this->hellosign_request_id &&
            $this->driver &&
            $this->driver->email &&
            !empty($this->driver->email);
    }

    /**
     * Get e-signature status badge
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
                return 'badge bg-light';
        }
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

    /**
     * Get e-signature status text
     */
    public function getEsignStatusTextAttribute()
    {
        if (!$this->hellosign_status) {
            return 'Not Sent';
        }
        return ucfirst($this->hellosign_status);
    }
}
