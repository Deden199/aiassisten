<?php

namespace Tests\Unit;

use App\Models\{AiProject, AiTask, AiTaskVersion, Tenant, User};
use App\Services\PptxExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'status'        => 'succeeded',
            'message'       => '',
        ]);

        $version = AiTaskVersion::create([
            'id'      => (string) Str::uuid(),
            'task_id' => $task->id,
            'locale'  => 'en',
            'payload' => [
                [
                    'title'   => 'Intro',
                    'bullets' => ['One', 'Two'],
                    'notes'   => 'Note one',
                ],
            ],
        ]);

        $exporter = new PptxExporter();
        $exporter->export($version);

        $version->refresh();
        $this->assertNotNull($version->file_path);
        Storage::disk('private')->assertExists($version->file_path);
    }
}
