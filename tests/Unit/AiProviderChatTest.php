<?php

namespace Tests\Unit;

use App\Services\AiProvider;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;

class AiProviderChatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Facade::clearResolvedInstances();
        $container = new Container();
        Container::setInstance($container);
        Facade::setFacadeApplication($container);
        $container->singleton('http', fn () => new Factory());
        $container->singleton('cache', fn () => new Repository(new ArrayStore()));
    }

    public function test_chat_returns_reply_text(): void
    {
        Http::fake(function ($request) {
            return Http::response([
                'usage' => ['prompt_tokens' => 5, 'completion_tokens' => 7],
                'choices' => [
                    ['message' => ['content' => 'Hi there!']],
                ],
            ]);
        });

        putenv('OPENAI_API_KEY=test');
        putenv('AI_PROVIDER=openai');

        $project = new class extends \App\Models\AiProject {
            protected static function booted(): void {}
        };

        $provider = new AiProvider();
        $result = $provider->chat($project, 'en', [
            ['role' => 'user', 'content' => 'Hello'],
        ]);

        $this->assertSame('Hi there!', AiProvider::extractContent($result));
        $this->assertSame(5, $result['input_tokens']);
        $this->assertSame(7, $result['output_tokens']);
    }

    public function test_chat_returns_error_when_openai_key_missing(): void
    {
        putenv('OPENAI_API_KEY');
        putenv('AI_PROVIDER=openai');

        $project = new class extends \App\Models\AiProject {
            protected static function booted(): void {}
        };

        $provider = new AiProvider();
        $result = $provider->chat($project, 'en', [
            ['role' => 'user', 'content' => 'Hello'],
        ]);

        $this->assertSame('missing openai api key', $result['error']['body'] ?? null);
        $this->assertSame('missing openai api key', $result['raw']['error'] ?? null);
    }

    public function test_chat_returns_error_when_anthropic_key_missing(): void
    {
        putenv('ANTHROPIC_API_KEY');
        putenv('AI_PROVIDER=anthropic');

        $project = new class extends \App\Models\AiProject {
            protected static function booted(): void {}
        };

        $provider = new AiProvider();
        $result = $provider->chat($project, 'en', [
            ['role' => 'user', 'content' => 'Hello'],
        ]);

        $this->assertSame('missing anthropic api key', $result['error']['body'] ?? null);
        $this->assertSame('missing anthropic api key', $result['raw']['error'] ?? null);
    }
}
