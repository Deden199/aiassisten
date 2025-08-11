<?php

namespace Tests\Unit;

use App\Services\AiProvider;
use PHPUnit\Framework\TestCase;

class AiProviderTest extends TestCase
{
    public function test_extract_content_from_openai_response(): void
    {
        $result = [
            'raw' => [
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Hello from OpenAI',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame('Hello from OpenAI', AiProvider::extractContent($result));
    }

    public function test_extract_content_from_anthropic_response(): void
    {
        $result = [
            'raw' => [
                'content' => [
                    [
                        'text' => 'Hello from Anthropic',
                    ],
                ],
            ],
        ];

        $this->assertSame('Hello from Anthropic', AiProvider::extractContent($result));
    }
}

