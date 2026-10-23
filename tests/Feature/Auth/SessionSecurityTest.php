<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('the session cookie is http only, same site and encrypted', function () {
    expect(config('session.http_only'))->toBeTrue()
        ->and(config('session.same_site'))->toBe('lax')
        ->and(config('session.encrypt'))->toBeTrue();
});

test('the session cookie is secure outside local', function () {
    // O ambiente de teste não é local, então vale o padrão de produção.
    expect(config('session.secure'))->toBeTrue();
});

test('logging in starts a new session id', function () {
    $user = User::factory()->create();

    $this->get(route('login'));
    $before = session()->getId();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect();

    expect(session()->getId())->not->toBe($before);
    $this->assertAuthenticatedAs($user);
});

describe('ending other sessions', function () {
    beforeEach(function () {
        $this->user = User::factory()->create(['remember_token' => 'old-token']);
        $this->other = User::factory()->create();

        foreach ([[$this->user, 'phone'], [$this->user, 'laptop'], [$this->other, 'theirs']] as [$owner, $id]) {
            DB::table('sessions')->insert([
                'id' => $id,
                'user_id' => $owner->id,
                'payload' => '',
                'last_activity' => now()->timestamp,
            ]);
        }
    });

    test('requires the current password', function () {
        $this->actingAs($this->user)
            ->delete(route('sessions.destroy-others'), ['password' => 'wrong'])
            ->assertSessionHasErrors('password');

        expect(DB::table('sessions')->count())->toBe(3);
    });

    test('removes the other sessions of the student only', function () {
        $this->actingAs($this->user)
            ->delete(route('sessions.destroy-others'), ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'other-sessions-ended');

        expect(DB::table('sessions')->pluck('id')->all())->toBe(['theirs'])
            ->and($this->user->fresh()->remember_token)->not->toBe('old-token');
        $this->assertAuthenticatedAs($this->user);
    });
});

test('there are no api token routes', function () {
    $uris = collect(app('router')->getRoutes()->getRoutes())->map->uri();

    expect($uris->filter(fn (string $uri) => str_starts_with($uri, 'api/')))->toBeEmpty()
        ->and(class_exists('Laravel\\Sanctum\\Sanctum'))->toBeFalse();
});
