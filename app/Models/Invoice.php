<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'subscription_id',
        'stripe_invoice_id',
        'invoice_number',
        'amount',
        'tax',
        'total',
        'status',
        'paid_at',
        'due_date',
        'pdf_path'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'due_date' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    // ==================== HELPER METHODS ====================

    public function markAsPaid()
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now()
        ]);

        return $this;
    }

    public function markAsFailed()
    {
        $this->update(['status' => 'failed']);
        return $this;
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isOverdue(): bool
    {
        return $this->due_date &&
            $this->due_date < now() &&
            !$this->isPaid();
    }

    public function getFormattedAmount()
    {
        return '£' . number_format($this->amount, 2);
    }

    public function getFormattedTotal()
    {
        return '£' . number_format($this->total, 2);
    }

    // ==================== SCOPES ====================

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', 'paid');
    }

    // ==================== STATIC METHODS ====================

    public static function generateInvoiceNumber()
    {
        $year = now()->year;
        $lastInvoice = self::whereYear('created_at', $year)
            ->latest('id')
            ->first();

        $nextNumber = $lastInvoice ? (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;

        return 'INV-' . $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
