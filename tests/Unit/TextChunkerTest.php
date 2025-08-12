<?php

namespace Tests\Unit;

use App\Support\TextChunker;
use App\Support\Tokenizer;
use PHPUnit\Framework\TestCase;

class TextChunkerTest extends TestCase
{
    public function test_clean_removes_control_chars_and_normalizes_whitespace(): void
    {
        $input = "Hello\tWorld\n" . chr(0) . "Example";
        $expected = 'Hello World Example';
        $this->assertSame($expected, TextChunker::clean($input));
    }

    public function test_chunk_splits_text_by_token_count(): void
    {
        $text = 'one two three four five';
        $chunks = TextChunker::chunk($text, 2);
        $this->assertSame(['one two', 'three four', 'five'], $chunks);

        foreach ($chunks as $chunk) {
            $this->assertLessThanOrEqual(2, Tokenizer::count($chunk));
        }
    }
}
