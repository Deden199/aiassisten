<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UsageLog extends Model
{
    use HasUuids;

    protected $table = 'usage_logs';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Tabel ini hanya punya created_at (useCurrent), tidak ada updated_at
    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'task_id',
        'event',
        'data',
        'cost_cents',
        'tokens_in',
        'tokens_out',
        'created_at',
    ];

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    public function tenant() { return $this->belongsTo(Tenant::class); }
    public function user()   { return $this->belongsTo(User::class); }
}
