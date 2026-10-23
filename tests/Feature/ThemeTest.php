<?php

test('explicit theme preference is rendered on the html element', function (string $appearance) {
    $this->withUnencryptedCookie('appearance', $appearance)
        ->get('/')
        ->assertOk()
        ->assertSee('data-theme="'.$appearance.'"', false);
})->with(['light', 'dark']);

test('automatic theme leaves the choice to the browser preference', function () {
    $this->withUnencryptedCookie('appearance', 'system')
        ->get('/')
        ->assertOk()
        ->assertDontSee('<html lang="pt-BR" data-theme', false)
        ->assertSee('prefers-color-scheme: dark', false);
});

test('unexpected theme values are never rendered', function () {
    $this->withUnencryptedCookie('appearance', '"><script>alert(1)</script>')
        ->get('/')
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('data-theme="', false);
});
