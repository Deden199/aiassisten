<?php

namespace Tests\Feature;

use App\Jobs\ProcessAiTask;
use App\Models\{AiProject, AiTask, Tenant, User, SlideTemplate, AiTaskVersion};
use App\Services\AiProvider;
use App\Services\PptxExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class SlideTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_slides_with_template(): void
    {
        Storage::fake('private');

        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'T',
            'slug' => 't',
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $template = SlideTemplate::create(array_merge(
            SlideTemplate::defaultTheme(),
            ['id' => (string) Str::uuid(), 'tenant_id' => $tenant->id, 'name' => 'Custom']
        ));

        $project = AiProject::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'title' => 'Test',
            'language' => 'en',
            'status' => 'ready',
            'slide_template_id' => $template->id,
            'source_text' => 'source',
        ]);

        $task = AiTask::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'project_id' => $project->id,
            'type' => 'slides',
            'status' => 'queued',
            'message' => '',
            'input_tokens' => 0,
            'output_tokens' => 0,
            'cost_cents' => 0,
        ]);

        $content = json_encode([
            'theme' => [],
            'slides' => [
                ['title' => 'Hello', 'bullets' => ['a','b'], 'notes' => null],
            ],
        ]);

        $provider = Mockery::mock(AiProvider::class);
        $provider->shouldReceive('generate')->andReturn([
            'raw' => ['choices' => [['message' => ['content' => $content]]]],
            'input_tokens' => 1,
            'output_tokens' => 1,
            'cost_cents' => 1,
        ]);

        $job = new ProcessAiTask($task, 'en');
        $job->handle($provider, app(\App\Services\DocumentParser::class));

        $version = AiTaskVersion::where('task_id', $task->id)->first();
        $this->assertNotNull($version);
        $this->assertIsArray($version->payload['slides'] ?? null);

        $exporter = new PptxExporter();
        $exporter->export($version);
        $version->refresh();
        Storage::disk('private')->assertExists($version->file_path);
    }
}
