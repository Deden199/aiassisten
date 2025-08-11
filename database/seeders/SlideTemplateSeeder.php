<?php

namespace Database\Seeders;

use App\Models\SlideTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SlideTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = \App\Models\Tenant::where('slug', 'demo')->value('id');

        $templates = [
            [
                'name' => 'Dark Gradient',
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
                    'title' => ['x'=>30,'y'=>30,'w'=>900,'h'=>80,'align'=>'left'],
                    'bullets' => ['x'=>40,'y'=>130,'w'=>900,'h'=>400,'line_spacing'=>1.25,'indent'=>10],
                ],
                'background_default' => [
                    'type' => 'gradient',
                    'color' => '#0B1220',
                    'gradient' => ['from'=>'#0EA5E9','to'=>'#111827'],
                    'image_url' => null,
                ],
                'rules' => ['slides_min'=>5,'slides_max'=>10,'require_bullets'=>true,'use_gradient'=>true],
            ],
            [
                'name' => 'Light Minimal',
                'palette' => [
                    'background' => '#FFFFFF',
                    'primary' => '#1F2937',
                    'secondary' => '#4B5563',
                    'accent' => '#2563EB',
                ],
                'font' => [
                    'family' => 'Arial',
                    'title_size' => 40,
                    'body_size' => 22,
                    'title_weight' => 'bold',
                    'body_weight' => 'normal',
                ],
                'layout' => [
                    'title' => ['x'=>40,'y'=>40,'w'=>880,'h'=>80,'align'=>'center'],
                    'bullets' => ['x'=>60,'y'=>140,'w'=>860,'h'=>380,'line_spacing'=>1.15,'indent'=>20],
                ],
                'background_default' => [
                    'type' => 'solid',
                    'color' => '#FFFFFF',
                    'gradient' => null,
                    'image_url' => null,
                ],
                'rules' => ['slides_min'=>4,'slides_max'=>8,'require_bullets'=>true,'use_gradient'=>false],
            ],
            [
                'name' => 'Brandy',
                'palette' => [
                    'background' => '#1C1917',
                    'primary' => '#F97316',
                    'secondary' => '#FB923C',
                    'accent' => '#FDE68A',
                ],
                'font' => [
                    'family' => 'Georgia',
                    'title_size' => 48,
                    'body_size' => 26,
                    'title_weight' => 'bold',
                    'body_weight' => 'normal',
                ],
                'layout' => [
                    'title' => ['x'=>30,'y'=>40,'w'=>900,'h'=>90,'align'=>'left'],
                    'bullets' => ['x'=>50,'y'=>150,'w'=>860,'h'=>360,'line_spacing'=>1.3,'indent'=>20],
                ],
                'background_default' => [
                    'type' => 'gradient',
                    'color' => '#1C1917',
                    'gradient' => ['from'=>'#78350F','to'=>'#1C1917'],
                    'image_url' => null,
                ],
                'rules' => ['slides_min'=>6,'slides_max'=>12,'require_bullets'=>true,'use_gradient'=>true],
            ],
        ];

        foreach ($templates as $tpl) {
            SlideTemplate::updateOrCreate(
                ['tenant_id' => $tenantId, 'name' => $tpl['name']],
                array_merge($tpl, ['tenant_id' => $tenantId])
            );
        }
    }
}
