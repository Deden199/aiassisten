<?php

namespace App\Models;

use App\Models\Scopes\EnforceTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'gateway',
        'gateway_sub_id',
        'status',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new EnforceTenant);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
