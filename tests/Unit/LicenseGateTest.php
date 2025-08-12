<?php

namespace Tests\Unit;

use App\Http\Middleware\LicenseGate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\TestCase;

class LicenseGateTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        putenv('LICENSE_BYPASS');
        unset($_ENV['LICENSE_BYPASS'], $_SERVER['LICENSE_BYPASS']);
    }

    public function test_bypass_env_allows_request(): void
    {
        putenv('LICENSE_BYPASS=true');
        $_ENV['LICENSE_BYPASS'] = true;
        $_SERVER['LICENSE_BYPASS'] = true;

        $middleware = new LicenseGate();
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn() => (object)[
            'tenant' => (object)[
                'license' => (object)['status' => 'expired']
            ]
        ]);

        $response = $middleware->handle($request, fn($req) => new Response('ok', 200));

        $this->assertEquals(200, $response->getStatusCode());
    }
}

