<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AiTaskVersion extends Model
{
    use HasUuids;

    protected $fillable = [
        'task_id',
        'locale',
        'payload',
        'file_disk',
        'file_path',
    ];

    protected $hidden = ['payload'];

    protected $casts = [
        'payload' => 'array',
    ];

    public function task()
    {
        return $this->belongsTo(AiTask::class, 'task_id');
    }
}
