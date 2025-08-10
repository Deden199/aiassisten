<?php

namespace App\Models;

use App\Models\Scopes\EnforceTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'purchase_code',
        'domain',
        'activated_at',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'activated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new EnforceTenant);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
