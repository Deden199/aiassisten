<?php

namespace App\Support;

use Yethee\Tiktoken\Encoder;
use Yethee\Tiktoken\EncoderProvider;

/**
 * Simple tokenizer wrapper around the tiktoken encoder.
 */
class Tokenizer
{
    /** @var Encoder|null */
    protected static ?Encoder $encoder = null;

    /**
     * Get the encoder instance for the configured model.
     */
    public static function encoder(): Encoder
    {
        if (self::$encoder instanceof Encoder) {
            return self::$encoder;
        }

        $provider = new EncoderProvider();
        $model = env('AI_MODEL', 'gpt-4o-mini');

        try {
            self::$encoder = $provider->getForModel($model);
        } catch (\Throwable $e) {
            // Fallback to a common encoding if model is unknown
            self::$encoder = $provider->get('cl100k_base');
        }

        return self::$encoder;
    }

    /**
     * Encode the given text into tokens.
     *
     * @return array<int>
     */
    public static function encode(string $text): array
    {
        return self::encoder()->encode($text);
    }

    /**
     * Decode tokens back into text.
     *
     * @param array<int> $tokens
     */
    public static function decode(array $tokens): string
    {
        return self::encoder()->decode($tokens);
    }

    /**
     * Count the number of tokens in the given text.
     */
    public static function count(string $text): int
    {
        return count(self::encode($text));
    }

    /**
     * Split text into chunks by token count.
     *
     * @return array<int,string>
     */
    public static function chunk(string $text, int $maxTokens): array
    {
        $chunks = [];
        $encoder = self::encoder();

        foreach ($encoder->encodeInChunks($text, $maxTokens) as $tokenChunk) {
            $chunks[] = trim($encoder->decode($tokenChunk));
        }

        return $chunks;
    }
}
