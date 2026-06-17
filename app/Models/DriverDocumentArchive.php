<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverDocumentArchive extends Model
{
    protected $fillable = [
        'driver_id',
        'document_field',
        'filename',
        'document_label',
        'reason',
        'archived_by',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    public function fileUrl(): string
    {
        return asset('uploads/driver_licenses/'.$this->filename);
    }

    public function reasonLabel(): string
    {
        return match ($this->reason) {
            'replaced' => 'Replaced',
            'removed' => 'Removed',
            default => ucfirst($this->reason),
        };
    }
}
