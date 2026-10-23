<?php

use Inertia\Testing\AssertableInertia as Assert;

test('the target language is shared with every page', function () {
    $this->get('/entrar')
        ->assertInertia(fn (Assert $page) => $page->where('targetLang.code', 'en'));
});

test('the target language comes from configuration', function () {
    config(['fixa.target_language' => 'es']);

    $this->get('/entrar')
        ->assertInertia(fn (Assert $page) => $page->where('targetLang.code', 'es'));
});
