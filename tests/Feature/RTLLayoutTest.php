<?php

namespace Tests\Feature;

use Tests\TestCase;

class RTLLayoutTest extends TestCase
{
    public function test_rtl_attribute_for_arabic(): void
    {
        $response = $this->get('/ar');
        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
    }
}
