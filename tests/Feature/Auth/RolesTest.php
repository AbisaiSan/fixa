<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fixtures\OwnedNote;
use Tests\Fixtures\OwnedNotePolicy;

test('new accounts are students', function () {
    expect(User::factory()->create()->role)->toBe(Role::Student);
});

test('the role cannot be mass assigned', function () {
    $user = User::create([
        'name' => 'Marina',
        'email' => 'marina@example.com',
        'password' => 'senha-segura-123',
        'role' => 'admin',
    ]);

    expect($user->fresh()->role)->toBe(Role::Student);
});

test('registration never creates an admin', function () {
    $this->post(route('register'), [
        'name' => 'Marina',
        'email' => 'marina@example.com',
        'password' => 'senha-segura-123',
        'password_confirmation' => 'senha-segura-123',
        'role' => 'admin',
        'terms' => true,
    ]);

    expect(User::where('email', 'marina@example.com')->first()?->isAdmin())->toBeFalse();
});

test('an admin is promoted and revoked only from the terminal', function () {
    $user = User::factory()->create(['email' => 'admin@example.com']);

    $this->artisan('fixa:admin', ['email' => 'ADMIN@example.com'])->assertSuccessful();
    expect($user->fresh()->isAdmin())->toBeTrue();

    $this->artisan('fixa:admin', ['email' => 'admin@example.com', '--revoke' => true])->assertSuccessful();
    expect($user->fresh()->isAdmin())->toBeFalse();

    $this->artisan('fixa:admin', ['email' => 'ninguem@example.com'])->assertFailed();
});

test('the browser never receives the numeric user id', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('today'))->assertInertia(fn (Assert $page) => $page
        ->where('auth.user.ulid', $user->ulid)
        ->where('auth.user.is_admin', false)
        ->missing('auth.user.id')
        ->missing('auth.user.two_factor_secret'));
});

describe('owned model policy', function () {
    beforeEach(function () {
        Schema::create('owned_notes', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });
        Gate::policy(OwnedNote::class, OwnedNotePolicy::class);

        $this->owner = User::factory()->create();
        $this->note = (new OwnedNote(['body' => 'x']))->forceFill(['user_id' => $this->owner->id]);
        $this->note->save();
    });

    test('allows the owner', function (string $ability) {
        expect(Gate::forUser($this->owner)->allows($ability, $this->note))->toBeTrue();
    })->with(['view', 'update', 'delete']);

    test('denies another student and an admin', function (string $ability) {
        $student = User::factory()->create();
        $admin = User::factory()->create();
        $admin->role = Role::Admin;
        $admin->save();

        expect(Gate::forUser($student)->allows($ability, $this->note))->toBeFalse()
            ->and(Gate::forUser($admin)->allows($ability, $this->note))->toBeFalse();
    })->with(['view', 'update', 'delete']);
});
