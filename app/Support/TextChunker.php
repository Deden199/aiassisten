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
     * Split text into chunks by token count. Tokens are approximated
     * by splitting on whitespace.
     *
     * @return array<int,string>
     */
    public static function chunk(string $text, int $maxTokens = 800): array
    {
        $clean = self::clean($text);
        if ($clean === '') {
            return [];
        }

        $tokens = preg_split('/\s+/u', $clean, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $current = [];

        foreach ($tokens as $token) {
            $current[] = $token;
            if (count($current) >= $maxTokens) {
                $chunks[] = implode(' ', $current);
                $current = [];
            }
        }

        if ($current) {
            $chunks[] = implode(' ', $current);
        }

        return $chunks;
    }
}
