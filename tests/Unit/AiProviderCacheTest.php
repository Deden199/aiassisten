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

class AiProviderCacheTest extends TestCase
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

    public function test_cached_results_skip_api_call(): void
    {
        $count = 0;
        Http::fake(function ($request) use (&$count) {
            $count++;
            return Http::response([
                'usage' => ['prompt_tokens' => 0, 'completion_tokens' => 0],
                'choices' => [["message" => ['content' => '{}']]],
            ]);
        });

        $project = new class extends \App\Models\AiProject {
            protected static function booted(): void {}
            public $slideTemplate = null;
        };

        $provider = new AiProvider();
        $first = $provider->generate($project, 'summary', 'en', 'Demo text');
        $second = $provider->generate($project, 'summary', 'en', 'Demo text');

        $this->assertSame($first, $second);
        $this->assertSame(1, $count);
    }

    public function test_cache_can_be_bypassed(): void
    {
        $count = 0;
        Http::fake(function ($request) use (&$count) {
            $count++;
            return Http::response([
                'usage' => ['prompt_tokens' => 0, 'completion_tokens' => 0],
                'choices' => [["message" => ['content' => '{}']]],
            ]);
        });

        $project = new class extends \App\Models\AiProject {
            protected static function booted(): void {}
            public $slideTemplate = null;
        };

        $provider = new AiProvider();
        $provider->generate($project, 'summary', 'en', 'Demo text');
        $provider->withoutCache()->generate($project, 'summary', 'en', 'Demo text');

        $this->assertSame(2, $count);
    }
}

