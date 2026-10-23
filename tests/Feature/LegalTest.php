<?php

use App\Models\Consent;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

$registration = fn (array $overrides = []) => [
    'name' => 'Marina Alves',
    'email' => 'marina@example.com',
    'password' => 'senha-segura-123',
    'password_confirmation' => 'senha-segura-123',
    'terms' => true,
    ...$overrides,
];

test('registration requires accepting the terms', function () use ($registration) {
    $this->post(route('register.store'), $registration(['terms' => false]))
        ->assertSessionHasErrors(['terms' => 'Para continuar, marque o aceite dos termos.']);

    expect(User::count())->toBe(0);
});

test('registration records terms and privacy acceptance with version, date and ip', function () use ($registration) {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->post(route('register.store'), $registration());

    $consents = User::where('email', 'marina@example.com')->firstOrFail()->consents()->get();

    expect($consents->pluck('document')->sort()->values()->all())->toBe(['privacy', 'terms'])
        ->and($consents->pluck('version')->unique()->all())->toBe([config('fixa.legal.terms')])
        ->and($consents->pluck('ip_address')->unique()->all())->toBe(['203.0.113.7'])
        ->and($consents->every(fn (Consent $consent) => $consent->accepted_at !== null))->toBeTrue();
});

test('terms and privacy are public and marked as draft', function (string $route, string $title) {
    $this->get(route($route))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('legal/Document')
        ->where('title', $title)
        ->where('draft', true)
        ->has('sections'));
})->with([['legal.terms', 'Termos de uso'], ['legal.privacy', 'Política de privacidade']]);

describe('a new version of the documents', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        config(['fixa.legal.terms' => '2027-01-01']);
    });

    test('sends the student to accept it before anything else', function () {
        $this->actingAs($this->user)->get(route('dashboard'))->assertRedirect(route('legal.accept'));
    });

    test('is recorded when accepted and the student goes on', function () {
        $this->actingAs($this->user)
            ->post(route('legal.accept.store'), ['terms' => true])
            ->assertRedirect();

        expect($this->user->fresh()->hasAcceptedCurrentLegal())->toBeTrue();
        $this->actingAs($this->user)->get(route('dashboard'))->assertOk();
    });

    test('still requires the checkbox', function () {
        $this->actingAs($this->user)
            ->post(route('legal.accept.store'))
            ->assertSessionHasErrors('terms');

        expect($this->user->fresh()->hasAcceptedCurrentLegal())->toBeFalse();
    });

    test('keeps the previous acceptance as history', function () {
        $this->actingAs($this->user)->post(route('legal.accept.store'), ['terms' => true]);

        expect($this->user->consents()->where('document', 'terms')->pluck('version')->sort()->values()->all())
            ->toBe(['2026-10-23', '2027-01-01']);
    });
});
