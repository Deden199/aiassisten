<?php

namespace Tests\Unit;

use App\Models\{AiProject, AiTask, AiTaskVersion, Tenant, User, SlideTemplate};
use App\Services\PptxExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpPresentation\IOFactory;
use Tests\TestCase;

class PptxExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_exports_pptx_and_updates_version(): void
    {
        Storage::fake('private');

        $tenant = Tenant::create([
            'id'   => (string) Str::uuid(),
            'name' => 'T',
            'slug' => 't',
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $project = AiProject::create([
            'id'              => (string) Str::uuid(),
            'tenant_id'       => $tenant->id,
            'user_id'         => $user->id,
            'title'           => 'Test',
            'source_filename' => null,
            'source_disk'     => 'private',
            'source_path'     => null,
            'language'        => 'en',
            'status'          => 'ready',
            'source_text'     => '',
        ]);

        $task = AiTask::create([
            'id'            => (string) Str::uuid(),
            'tenant_id'     => $tenant->id,
            'user_id'       => $user->id,
            'project_id'    => $project->id,
            'type'          => 'slides',
            'input_tokens'  => 0,
            'output_tokens' => 0,
            'cost_cents'    => 0,
            'status'        => 'done',
            'message'       => '',
        ]);

        $version = AiTaskVersion::create([
            'id'      => (string) Str::uuid(),
            'task_id' => $task->id,
            'locale'  => 'en',
            'payload' => [
                'theme' => SlideTemplate::defaultTheme(),
                'slides' => [
                    [
                        'title'   => 'Intro',
                        'bullets' => ['One', 'Two'],
                        'notes'   => 'Note one',
                    ],
                ],
            ],
        ]);

        $exporter = new PptxExporter();
        $exporter->export($version);

        $version->refresh();
        $this->assertNotNull($version->file_path);
        Storage::disk('private')->assertExists($version->file_path);
    }

    public function test_sanitizes_malformed_input(): void
    {
        Storage::fake('private');

        $tenant = Tenant::create([
            'id'   => (string) Str::uuid(),
            'name' => 'T',
            'slug' => 't',
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $project = AiProject::create([
            'id'              => (string) Str::uuid(),
            'tenant_id'       => $tenant->id,
            'user_id'         => $user->id,
            'title'           => 'Test',
            'source_filename' => null,
            'source_disk'     => 'private',
            'source_path'     => null,
            'language'        => 'en',
            'status'          => 'ready',
            'source_text'     => '',
        ]);

        $task = AiTask::create([
            'id'            => (string) Str::uuid(),
            'tenant_id'     => $tenant->id,
            'user_id'       => $user->id,
            'project_id'    => $project->id,
            'type'          => 'slides',
            'input_tokens'  => 0,
            'output_tokens' => 0,
            'cost_cents'    => 0,
            'status'        => 'done',
            'message'       => '',
        ]);

        $version = AiTaskVersion::create([
            'id'      => (string) Str::uuid(),
            'task_id' => $task->id,
            'locale'  => 'en',
            'payload' => [
                'theme' => SlideTemplate::defaultTheme(),
                'slides' => [
                    [
                        'title'   => ' <h1>Intro</h1> ',
                        'bullets' => [' <b>One</b> ', null, '', 123, 'Two ', '<script>alert("x")</script>', '   '],
                        'notes'   => ['<i>Note</i> ', null],
                    ],
                ],
            ],
        ]);

        $exporter = new PptxExporter();
        $exporter->export($version);

        $version->refresh();
        $path = Storage::disk('private')->path($version->file_path);
        $presentation = IOFactory::load($path);
        $slide = $presentation->getSlide(0);
        $texts = [];
        foreach ($slide->getShapeCollection() as $shape) {
            foreach ($shape->getParagraphs() as $paragraph) {
                foreach ($paragraph->getRichTextElements() as $element) {
                    $text = $element->getText();
                    if (trim($text) !== '') {
                        $texts[] = $text;
                    }
                }
            }
        }

        $this->assertSame(['Intro', 'One', 'Two', 'alert("x")'], $texts);
    }
}
