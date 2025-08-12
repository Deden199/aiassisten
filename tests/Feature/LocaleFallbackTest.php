<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleFallbackTest extends TestCase
{
    public function test_missing_key_falls_back_to_english(): void
    {
        app()->setLocale('es');
        $this->assertSame('Only in English', __t('Missing'));
    }
}
