<?php

namespace App\Models;

use App\Models\Scopes\EnforceTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiProject extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'title',
        'source_filename',
        'source_disk',
        'source_path',
        'source_text',
        'language',
        'status',
        'slide_template_id',
        'error_message',
    ];

    protected $hidden = ['source_text'];

    protected static function booted(): void
    {
        static::addGlobalScope(new EnforceTenant);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tasks()
    {
        return $this->hasMany(AiTask::class, 'project_id');
    }

    public function slideTemplate()
    {
        return $this->belongsTo(SlideTemplate::class, 'slide_template_id');
    }
}
