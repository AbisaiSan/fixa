<?php

use Illuminate\Support\Facades\Route;

/*
| Rotas só de desenvolvimento. Carregadas apenas com APP_ENV=local (J0-F7).
*/

Route::middleware('web')->prefix('dev')->name('dev.')->group(function () {
    Route::inertia('componentes', 'dev/Catalog')->name('catalog');
});
