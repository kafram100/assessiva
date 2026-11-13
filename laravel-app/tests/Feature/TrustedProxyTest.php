<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_forwarded_host_is_ignored_when_no_proxy_is_trusted(): void
    {
        Route::get('/_test/trusted-host', fn (Request $request) => $request->getHost());

        $this->withHeader('X-Forwarded-Host', 'attacker.example')
            ->get('/_test/trusted-host')
            ->assertOk()
            ->assertSee('127.0.0.1');
    }
}
