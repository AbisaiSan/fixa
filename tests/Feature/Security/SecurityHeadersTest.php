<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

test('responses carry the security headers', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
        ->assertHeaderMissing('X-Powered-By');

    expect($response->headers->get('Permissions-Policy'))->toContain('camera=()');
});

test('content security policy uses a per-request nonce for scripts', function () {
    $first = $this->get('/');
    $csp = $first->headers->get('Content-Security-Policy');

    expect($csp)
        ->toContain("default-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'none'")
        ->toContain("form-action 'self'")
        ->not->toContain('unsafe-eval')
        ->not->toMatch("/script-src[^;]*'unsafe-inline'/");

    preg_match("/'nonce-([^']+)'/", $csp, $matches);
    $nonce = $matches[1];

    $first->assertSee('nonce="'.$nonce.'"', false);

    $second = $this->get('/');
    expect($second->headers->get('Content-Security-Policy'))->not->toContain($nonce);
});

test('error pages also carry the security headers', function () {
    $this->get('/does-not-exist')
        ->assertNotFound()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Content-Security-Policy');
});

test('hsts and upgrade-insecure-requests are only sent in production', function () {
    $this->get('/')->assertHeaderMissing('Strict-Transport-Security');

    app()->detectEnvironment(fn () => 'production');

    $response = $this->get('/');

    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    expect($response->headers->get('Content-Security-Policy'))
        ->toContain('upgrade-insecure-requests')
        ->toMatch("/style-src-elem 'self' 'nonce-[^']+';/")
        ->not->toContain(':5173');
});

test('cors does not allow cross-origin requests', function () {
    $this->withHeaders(['Origin' => 'https://evil.example'])
        ->get('/')
        ->assertHeaderMissing('Access-Control-Allow-Origin');
});

describe('request forgery prevention', function () {
    beforeEach(function () {
        // O middleware se desliga em testes; esta versão o mantém ativo.
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    });

    test('rejects a post without token', function () {
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'secret'])
            ->assertStatus(419);
    });

    test('rejects a cross-site post without token', function () {
        $this->withHeaders(['Sec-Fetch-Site' => 'cross-site'])
            ->post('/login', ['email' => 'a@example.com', 'password' => 'secret'])
            ->assertStatus(419);
    });

    test('rejects a same-site post from another subdomain', function () {
        $this->withHeaders(['Sec-Fetch-Site' => 'same-site'])
            ->post('/login', ['email' => 'a@example.com', 'password' => 'secret'])
            ->assertStatus(419);
    });

    test('accepts a same-origin post', function () {
        $this->withHeaders(['Sec-Fetch-Site' => 'same-origin'])
            ->post('/login', ['email' => 'a@example.com', 'password' => 'secret'])
            ->assertStatus(302);
    });
});
