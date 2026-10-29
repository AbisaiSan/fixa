<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/_test/scheme', fn () => request()->getSchemeAndHttpHost());
});

test('forwarded headers are ignored when no proxy is trusted', function () {
    config(['trustedproxy.proxies' => null]);

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.1'])
        ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'evil.example'])
        ->get('http://localhost/_test/scheme')
        ->assertSee('http://localhost', false)
        ->assertDontSee('evil.example', false);
});

test('forwarded headers are honored from a trusted proxy', function () {
    config(['trustedproxy.proxies' => '172.16.0.0/12']);

    $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.1'])
        ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'fixa.tailnet.ts.net'])
        ->get('http://localhost/_test/scheme')
        ->assertSee('https://fixa.tailnet.ts.net', false);
});

test('forwarded headers are ignored from an address outside the trusted range', function () {
    config(['trustedproxy.proxies' => '172.16.0.0/12']);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'evil.example'])
        ->get('http://localhost/_test/scheme')
        ->assertDontSee('evil.example', false);
});
