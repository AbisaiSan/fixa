<?php

use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

test('the component catalog does not exist outside local', function () {
    $this->get('/dev/componentes')->assertNotFound();
});

test('the component catalog is available in local', function () {
    app()->detectEnvironment(fn () => 'local');
    require base_path('routes/dev.php');
    Route::getRoutes()->refreshNameLookups();

    $this->get('/dev/componentes')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('dev/Catalog'));
});
