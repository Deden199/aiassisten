<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'role',              
        'usage_tokens',       
        'usage_cost_cents',   
        'plan_id',           
        'locale',            
        'timezone',          
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'usage_tokens'      => 'integer',
            'usage_cost_cents'  => 'integer',
        ];
    }

 
    public function scopeTenant(Builder $query, ?string $tenantId = null): Builder
    {
        $tenantId = $tenantId
            ?? auth()->user()?->tenant_id
            ?? request()->attributes->get('tenant_id')
            ?? request()->header('X-Tenant-ID')
            ?? request()->query('tenant_id')
            ?? session('tenant_id');

        if ($tenantId) {
            $query->where($query->qualifyColumn('tenant_id'), $tenantId);
        }

        return $query;
    }


    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }


    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Helper role
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }


    public function isTenantAdmin(): bool
    {
        return $this->role === 'admin' && ! is_null($this->tenant_id);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'admin' && is_null($this->tenant_id);
    }
}
