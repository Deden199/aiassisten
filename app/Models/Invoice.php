<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'provider',
        'provider_invoice_id',
        'amount_due_cents',
        'currency',
        'hosted_url',
        'pdf_url',
        'paid_at',
        'raw',
    ];

    protected $casts = [
        'raw' => 'array',
        'paid_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
