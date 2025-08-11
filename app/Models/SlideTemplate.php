<?php

namespace App\Models;

use App\Models\Scopes\EnforceTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SlideTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'name',
        'palette',
        'font',
        'layout',
        'background_default',
        'rules',
    ];

    protected $casts = [
        'palette' => 'array',
        'font' => 'array',
        'layout' => 'array',
        'background_default' => 'array',
        'rules' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new EnforceTenant);
    }

    public function projects()
    {
        return $this->hasMany(AiProject::class, 'slide_template_id');
    }

    public function scopeTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public static function defaultTheme(): array
    {
        return [
            'palette' => [
                'background' => '#0B1220',
                'primary' => '#60A5FA',
                'secondary' => '#A78BFA',
                'accent' => '#34D399',
            ],
            'font' => [
                'family' => null,
                'title_size' => 44,
                'body_size' => 24,
                'title_weight' => 'bold',
                'body_weight' => 'normal',
            ],
            'layout' => [
                'title' => ['x' => 30, 'y' => 30, 'w' => 900, 'h' => 80, 'align' => 'left'],
                'bullets' => ['x' => 40, 'y' => 130, 'w' => 900, 'h' => 400, 'line_spacing' => 1.25, 'indent' => 10],
            ],
            'background_default' => [
                'type' => 'gradient',
                'color' => '#0B1220',
                'gradient' => ['from' => '#0EA5E9', 'to' => '#111827'],
                'image_url' => null,
            ],
            'rules' => [
                'slides_min' => 5,
                'slides_max' => 10,
                'require_bullets' => true,
                'use_gradient' => true,
            ],
        ];
    }
}
