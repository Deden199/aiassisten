<?php

namespace App\Models;

use App\Models\Scopes\EnforceTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Models\User;

class UsageCounter extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'period_start',
        'period_end',
        'tokens_used',
        'requests_used',
        'cost_cents',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new EnforceTenant);
    }

    public static function currentFor(User $user, ?Carbon $start = null, ?Carbon $end = null): self
    {
        $start = $start ? Carbon::parse($start) : now()->startOfMonth();
        $end = $end ? Carbon::parse($end) : now()->endOfMonth();

        return static::firstOrCreate(
            ['user_id' => $user->id, 'period_start' => $start],
            ['tenant_id' => $user->tenant_id, 'period_end' => $end]
        );
    }
}
