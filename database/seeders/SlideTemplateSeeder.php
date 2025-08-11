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

            // Additional templates for more variety. These presets offer
            // different colour schemes, fonts and layouts to help users
            // create professional slides that stand out. Feel free to tweak
            // colours or sizes to suit your brand.
            [
                'name' => 'Modern Blue',
                'palette' => [
                    'background' => '#F3F4F6',
                    'primary'    => '#1D4ED8',
                    'secondary'  => '#3B82F6',
                    'accent'     => '#6366F1',
                ],
                'font' => [
                    'family'      => 'Helvetica',
                    'title_size'  => 42,
                    'body_size'   => 22,
                    'title_weight' => 'bold',
                    'body_weight'  => 'normal',
                ],
                'layout' => [
                    'title'   => ['x'=>40,'y'=>40,'w'=>880,'h'=>80,'align'=>'left'],
                    'bullets' => ['x'=>60,'y'=>140,'w'=>860,'h'=>380,'line_spacing'=>1.2,'indent'=>16],
                ],
                'background_default' => [
                    'type'    => 'solid',
                    'color'   => '#F3F4F6',
                    'gradient'=> null,
                    'image_url'=> null,
                ],
                'rules' => ['slides_min'=>4,'slides_max'=>9,'require_bullets'=>true,'use_gradient'=>false],
            ],
            [
                'name' => 'Retro Neon',
                'palette' => [
                    'background' => '#0F172A',
                    'primary'    => '#FB7185',
                    'secondary'  => '#A78BFA',
                    'accent'     => '#FCD34D',
                ],
                'font' => [
                    'family'      => 'Courier New',
                    'title_size'  => 50,
                    'body_size'   => 24,
                    'title_weight' => 'bold',
                    'body_weight'  => 'normal',
                ],
                'layout' => [
                    'title'   => ['x'=>30,'y'=>30,'w'=>920,'h'=>90,'align'=>'center'],
                    'bullets' => ['x'=>60,'y'=>140,'w'=>860,'h'=>380,'line_spacing'=>1.4,'indent'=>20],
                ],
                'background_default' => [
                    'type'    => 'gradient',
                    'color'   => '#0F172A',
                    'gradient'=> ['from'=>'#1E3A8A','to'=>'#0F172A'],
                    'image_url'=> null,
                ],
                'rules' => ['slides_min'=>5,'slides_max'=>11,'require_bullets'=>true,'use_gradient'=>true],
            ],
            [
                'name' => 'Earthy Green',
                'palette' => [
                    'background' => '#F0F9F0',
                    'primary'    => '#065F46',
                    'secondary'  => '#10B981',
                    'accent'     => '#A3E635',
                ],
                'font' => [
                    'family'      => 'Georgia',
                    'title_size'  => 46,
                    'body_size'   => 24,
                    'title_weight' => 'bold',
                    'body_weight'  => 'normal',
                ],
                'layout' => [
                    'title'   => ['x'=>50,'y'=>50,'w'=>880,'h'=>100,'align'=>'left'],
                    'bullets' => ['x'=>70,'y'=>170,'w'=>840,'h'=>350,'line_spacing'=>1.25,'indent'=>18],
                ],
                'background_default' => [
                    'type'    => 'solid',
                    'color'   => '#F0F9F0',
                    'gradient'=> null,
                    'image_url'=> null,
                ],
                'rules' => ['slides_min'=>5,'slides_max'=>10,'require_bullets'=>true,'use_gradient'=>false],
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
