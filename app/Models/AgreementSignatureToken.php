<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AgreementSignatureToken extends Model
{
    protected $fillable = [
        'agreement_id',
        'token',
        'signer_email',
        'signer_name',
        'status',
        'signature_data',
        'ip_address',
        'signed_at',
        'expires_at'
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function agreement()
    {
        return $this->belongsTo(Agreement::class);
    }

    /**
     * Generate unique token
     */
    public static function generateToken()
    {
        return Str::random(64);
    }

    /**
     * Check if expired
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if signed
     */
    public function isSigned()
    {
        return $this->status === 'signed';
    }

    /**
     * Mark as signed
     */
    public function markAsSigned($signatureData, $ipAddress)
    {
        $this->update([
            'status' => 'signed',
            'signature_data' => $signatureData,
            'ip_address' => $ipAddress,
            'signed_at' => now()
        ]);
    }
}
