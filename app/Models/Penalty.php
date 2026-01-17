<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penalty extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'agreement_id', 'date', 'due_date',
        'amount', 'document', 'status_id'
    ];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function agreement()
    {
        return $this->belongsTo(Agreement::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }
}
