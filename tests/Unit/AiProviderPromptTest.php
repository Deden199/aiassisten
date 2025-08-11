<?php

namespace Tests\Unit;

use App\Services\AiProvider;
use Illuminate\Container\Container;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;

class AiProviderPromptTest extends TestCase
{
    public function test_prompt_placeholders_are_resolved(): void
    {
        Facade::clearResolvedInstances();
        $container = new Container();
        Container::setInstance($container);
        Facade::setFacadeApplication($container);
        $container->singleton('http', fn () => new Factory());
        $container->singleton('cache', fn () => new Repository(new ArrayStore()));

        $captured = null;
        Http::fake(function ($request) use (&$captured) {
            $captured = $request->data()['messages'][0]['content'] ?? '';
            return Http::response([
                'usage' => ['prompt_tokens' => 0, 'completion_tokens' => 0],
                'choices' => [["message" => ['content' => '{}']]],
            ]);
        });

        putenv('OPENAI_API_KEY=test');
        putenv('AI_PROVIDER=openai');

        $project = new class extends \App\Models\AiProject {
            protected static function booted(): void {}
            public $slideTemplate = null;
        };

        $provider = new AiProvider();
        $provider->generate($project, 'slides', 'id', 'Demo {{danger}} text');

        $template = \App\Models\SlideTemplate::defaultTheme();
        $themeJson = json_encode([
            'theme' => [
                'palette' => $template['palette'],
                'font' => $template['font'],
                'layout' => $template['layout'],
                'background_default' => $template['background_default'],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->assertNotNull($captured);
        $this->assertStringContainsString('Buat 5–10 slides', $captured);
        $this->assertStringContainsString($themeJson, $captured);
        $this->assertStringContainsString('Demo {danger} text', $captured);
        $this->assertStringNotContainsString('{{', $captured);
    }
}
