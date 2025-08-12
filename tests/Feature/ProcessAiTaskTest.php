<?php

namespace Tests\Feature;

use App\Jobs\ProcessAiTask;
use App\Models\{AiProject, AiTask, Tenant, User, AiTaskVersion};
use App\Services\AiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class ProcessAiTaskTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_mindmap_with_messy_json_is_parsed(): void
    {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'T',
            'slug' => 't',
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $project = AiProject::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'title' => 'Test',
            'language' => 'en',
            'status' => 'ready',
            'source_text' => 'source',
        ]);
        $task = AiTask::create([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'project_id' => $project->id,
            'type' => 'mindmap',
            'status' => 'queued',
            'message' => '',
            'input_tokens' => 0,
            'output_tokens' => 0,
            'cost_cents' => 0,
        ]);

        $provider = Mockery::mock(AiProvider::class);
        $provider->shouldReceive('generate')->andReturn([
            'raw' => ['choices' => [['message' => ['content' => 'mindmap: ["Idea1", "Idea2"]']]]],
            'input_tokens' => 1,
            'output_tokens' => 1,
            'cost_cents' => 1,
        ]);

        $ref = new \ReflectionClass(\App\Support\Tokenizer::class);
        $prop = $ref->getProperty('encoder');
        $prop->setAccessible(true);
        $prop->setValue(null, new class implements \Yethee\Tiktoken\Encoder {
            public function getEncoding(): string { return 'test'; }
            public function encode(string $text): array { return str_split($text); }
            public function encodeInChunks(string $text, int $maxTokensPerChunk): array { return [str_split($text)]; }
            public function decode(array $tokens): string { return implode('', $tokens); }
        });

        $job = new ProcessAiTask($task, 'en');
        $job->handle($provider, app(\App\Services\DocumentParser::class));

        $version = AiTaskVersion::where('task_id', $task->id)->first();
        $this->assertNotNull($version);
        $content = json_decode($version->payload['content'], true);
        $this->assertEquals(['Idea1', 'Idea2'], $content['mindmap']);
    }
}

