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
        putenv('OPENAI_API_KEY=test');
        putenv('AI_PROVIDER=openai');
    }

    public function test_messages_array_is_sent_to_provider(): void
    {
        $captured = null;
        Http::fake(function ($request) use (&$captured) {
            $captured = $request->data()['messages'] ?? null;
            return Http::response([
                'usage' => ['prompt_tokens' => 0, 'completion_tokens' => 0],
                'choices' => [["message" => ['content' => 'hi']]],
            ]);
        });

        $project = new class extends \App\Models\AiProject {
            protected static function booted(): void {}
            public $slideTemplate = null;
        };

        $provider = new AiProvider();
        $messages = [
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi'],
            ['role' => 'user', 'content' => 'How are you?'],
        ];

        $provider->chat($project, 'en', $messages);

        $this->assertSame($messages, $captured);
    }
}
