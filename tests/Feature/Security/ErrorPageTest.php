<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    config(['app.debug' => false]);

    Route::get('/_test/boom', fn () => throw new RuntimeException('student wrote: my secret answer'));
});

test('server errors show a generic page without details', function () {
    $this->get('/_test/boom')
        ->assertStatus(500)
        ->assertSee('Algo deu errado')
        ->assertDontSee('my secret answer')
        ->assertDontSee('RuntimeException')
        ->assertHeader('Content-Security-Policy');
});

test('server errors in json do not leak the exception message', function () {
    $this->getJson('/_test/boom')
        ->assertStatus(500)
        ->assertExactJson(['message' => 'Server Error']);
});

test('not found page is generic and in portuguese', function () {
    $this->get('/does-not-exist')
        ->assertNotFound()
        ->assertSee('Página não encontrada');
});
