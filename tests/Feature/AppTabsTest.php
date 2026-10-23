<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

dataset('tabs', [
    'Hoje' => ['/hoje', 'app/Today'],
    'Aulas' => ['/aulas', 'app/Lessons'],
    'Praticar' => ['/praticar', 'app/Practice'],
    'Vocabulário' => ['/vocabulario', 'app/Vocabulary'],
    'Progresso' => ['/progresso', 'app/Progress'],
]);

test('each tab shows its empty state to a new student', function (string $path, string $component) {
    $this->actingAs(User::factory()->create())
        ->get($path)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component)->where('status', 'empty'));
})->with('tabs');

test('tabs require a signed in, verified student', function (string $path) {
    $this->get($path)->assertRedirect(route('login'));

    $this->actingAs(User::factory()->unverified()->create())->get($path)->assertRedirect(route('verification.notice'));
})->with('tabs');

test('the root sends guests to sign in and students to today', function () {
    $this->get('/')->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('today'));
});

test('signing in lands on today', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('today', absolute: false));
});

test('the upload page is reachable from the empty states and carries the limits', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('lessons.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('app/LessonUpload')
            ->where('limits.max_megabytes', 10)
            ->where('limits.max_pages', 20)
            ->where('limits.min_words', 50));
});
