<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('users get a public ulid as route key', function () {
    $user = User::factory()->create();

    expect($user->ulid)->toHaveLength(26)
        ->and($user->getRouteKey())->toBe($user->ulid);
});

test('application and database use utc', function () {
    expect(config('app.timezone'))->toBe('UTC')
        ->and(DB::selectOne('show timezone')->TimeZone)->toBe('UTC');
});
