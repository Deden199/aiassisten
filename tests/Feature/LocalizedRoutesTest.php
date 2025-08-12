<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalizedRoutesTest extends TestCase
{
    public function test_localized_route_returns_ok(): void
    {
        $response = $this->get('/es');
        $response->assertStatus(200);
        $response->assertSee('Bienvenido');
    }
}
