<?php

namespace App\Models;

use App\Models\Scopes\EnforceTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'session_id',
        'role',
        'content',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new EnforceTenant);
    }

    public function session()
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }
}

