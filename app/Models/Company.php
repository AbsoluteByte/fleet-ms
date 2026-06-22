<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'director_name', 'company_registration_number', 'logo', 'address_line_1',
        'address_line_2', 'postcode', 'town', 'county',
        'country_id', 'phone', 'email', 'tenant_id', 'createdBy', 'updatedBy',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }

    public function agreements()
    {
        return $this->hasMany(Agreement::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
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
            $this->address_line_1,
            $this->address_line_2,
            $this->town,
            $this->county,
            $this->postcode,
        ]);
    }
}
