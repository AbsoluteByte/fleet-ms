<?php
namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarRoadTax extends Model
{
    use HasFactory;

    protected $fillable = ['tenant_id', 'car_id', 'start_date', 'term', 'amount'];

    protected $casts = [
        'start_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * Last day the tax is valid (UK VED: inclusive end date, day before renewal).
     * e.g. start 1 Dec 2025 + 6 months → expires 31 May 2026, not 1 Jun 2026.
     */
    public function expiryDate(): ?Carbon
    {
        if (! $this->start_date || ! $this->term) {
            return null;
        }

        $renewalDate = match (strtolower($this->term)) {
            '6 months' => $this->start_date->copy()->addMonths(6),
            '12 months', '1 year' => $this->start_date->copy()->addYear(),
            default => $this->renewalDateFromTermString(),
        };

        return $renewalDate?->subDay();
    }

    private function renewalDateFromTermString(): ?Carbon
    {
        if (! preg_match('/(\d+)\s*(month|year)/', strtolower($this->term), $matches)) {
            return null;
        }

        $number = (int) $matches[1];

        return match ($matches[2]) {
            'month' => $this->start_date->copy()->addMonths($number),
            'year' => $this->start_date->copy()->addYears($number),
            default => null,
        };
    }
}
