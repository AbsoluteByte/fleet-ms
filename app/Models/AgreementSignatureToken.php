<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AgreementSignatureToken extends Model
{
    public const METHOD_DRAW = 'draw';

    public const METHOD_TYPED = 'typed';

    protected $fillable = [
        'agreement_id',
        'token',
        'signer_email',
        'signer_name',
        'status',
        'signature_data',
        'signature_method',
        'typed_name',
        'ip_address',
        'signed_at',
        'expires_at',
        'opened_at',
        'opened_ip',
        'referrer',
        'user_agent',
        'accept_language',
        'landing_url',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
        'expires_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function agreement()
    {
        return $this->belongsTo(Agreement::class);
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->expires_at->isPast();
    }

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }

    public function recordFirstOpen(Request $request): void
    {
        if ($this->opened_at) {
            return;
        }

        $this->update([
            'opened_at' => now(),
            'opened_ip' => $request->ip(),
            'referrer' => $this->limitText($request->headers->get('referer')),
            'user_agent' => $this->limitText($request->userAgent()),
            'accept_language' => Str::limit((string) $request->headers->get('accept-language'), 255, ''),
            'landing_url' => $this->limitText($request->fullUrl()),
        ]);
    }

    /**
     * @param  array{signature_method?: string, typed_name?: string|null}  $meta
     */
    public function markAsSigned(string $signatureData, string $ipAddress, array $meta = []): void
    {
        $this->update([
            'status' => 'signed',
            'signature_data' => $signatureData,
            'signature_method' => $meta['signature_method'] ?? self::METHOD_DRAW,
            'typed_name' => filled($meta['typed_name'] ?? null) ? trim((string) $meta['typed_name']) : null,
            'ip_address' => $ipAddress,
            'signed_at' => now(),
        ]);
    }

    private function limitText(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return Str::limit($value, 2000, '');
    }
}
