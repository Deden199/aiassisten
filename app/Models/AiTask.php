<?php

namespace App\Models;

use App\Models\Scopes\EnforceTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiTask extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'project_id',
        'type',
        'input_tokens',
        'output_tokens',
        'cost_cents',
        'status',
        'message',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new EnforceTenant);
    }

    public function project()
    {
        return $this->belongsTo(AiProject::class, 'project_id');
    }

    public function versions()
    {
        return $this->hasMany(AiTaskVersion::class, 'task_id');
    }
}
