<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgreementCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id','agreement_id', 'date', 'due_date', 'method', 'amount',
        'payment_status', 'amount_paid', 'payment_date', 'notes', 'is_auto_generated'
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'is_auto_generated' => 'boolean'
    ];

    public function agreement()
    {
        return $this->belongsTo(Agreement::class);
    }

    public function getStatusBadgeClassAttribute()
    {
        return match($this->payment_status) {
            'paid' => 'badge-success',
            'pending' => 'badge-warning',
            'overdue' => 'badge-danger',
            'partial' => 'badge-info',
            default => 'badge-secondary'
        };
    }

    public function getDaysOverdueAttribute()
    {
        if ($this->payment_status !== 'overdue') {
            return 0;
        }
        $daysPassed = $this->due_date->diffInDays(now());

        return (int) abs($daysPassed);
    }

    public function getRemainingAmountAttribute()
    {
        return $this->amount - ($this->amount_paid ?? 0);
    }

    public function markAsPaid($amountPaid = null, $paymentDate = null)
    {
        $amountPaid = $amountPaid ?? $this->amount;
        $paymentDate = $paymentDate ?? now();

        $this->update([
            'amount_paid' => $amountPaid,
            'payment_date' => $paymentDate,
            'payment_status' => $amountPaid >= $this->amount ? 'paid' : 'partial'
        ]);

        $nextCollection = $this->agreement->collections()
            ->where('payment_status', 'pending')
            ->orderBy('due_date')
            ->first();

        $this->agreement->update([
            'next_collection_date' => $nextCollection?->due_date
        ]);
    }
}
