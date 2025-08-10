<?php

namespace App\Models;

use App\Models\Scopes\EnforceTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',         // admin, user, dll.
        'usage_tokens', // total token AI terpakai
        'usage_cost',   // total biaya terpakai
        'plan_id',      // untuk billing
        'locale',       // bahasa pilihan user
        'timezone',     // zona waktu user
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'usage_tokens' => 'integer',
            'usage_cost' => 'decimal:6',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new EnforceTenant);
    }

    /**
     * Relasi ke tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relasi ke plan
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Cek apakah user ini admin tenant
     */
    public function isTenantAdmin(): bool
    {
        return $this->role === 'admin' && ! is_null($this->tenant_id);
    }

    /**
     * Cek apakah user ini super admin (global)
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin' && is_null($this->tenant_id);
    }
}
