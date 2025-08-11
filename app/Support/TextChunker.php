<?php

namespace App\Support;

class TextChunker
{
    /**
     * Clean the given text by removing control characters
     * and normalizing whitespace.
     */
    public static function clean(string $text): string
    {
        $text = preg_replace('/[[:cntrl:]]+/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text);
    }

    /**
     * Split text into chunks by token count using the tokenizer service.
     *
     * @return array<int,string>
     */
    public static function chunk(string $text, int $maxTokens = 15000): array
    {
        $clean = self::clean($text);
        if ($clean === '') {
            return [];
        }

        return Tokenizer::chunk($clean, $maxTokens);
    }
}
