<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Fixtures\OwnedNote;

beforeEach(function () {
    Schema::create('owned_notes', function (Blueprint $table) {
        $table->id();
        $table->ulid()->unique();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->text('body');
        $table->timestamps();
    });

    $this->alice = User::factory()->create();
    $this->bob = User::factory()->create();

    foreach ([[$this->alice, 'alice note'], [$this->bob, 'bob note']] as [$owner, $body]) {
        (new OwnedNote(['body' => $body]))->forceFill(['user_id' => $owner->id])->save();
    }
});

test('queries only return records of the authenticated user', function () {
    $this->actingAs($this->alice);

    expect(OwnedNote::pluck('body')->all())->toBe(['alice note'])
        ->and(OwnedNote::count())->toBe(1);
});

test('a record of another user cannot be found by id or ulid', function () {
    $bobNote = OwnedNote::forUser($this->bob)->firstOrFail();

    $this->actingAs($this->alice);

    expect(OwnedNote::find($bobNote->id))->toBeNull()
        ->and(OwnedNote::where('ulid', $bobNote->ulid)->first())->toBeNull();
});

test('a record of another user cannot be updated or deleted through queries', function () {
    $this->actingAs($this->alice);

    OwnedNote::query()->update(['body' => 'changed']);
    OwnedNote::query()->delete();

    expect(OwnedNote::forUser($this->bob)->value('body'))->toBe('bob note');
});

test('without an authenticated user no records are returned', function () {
    expect(OwnedNote::count())->toBe(0);
});

test('forUser reads records explicitly outside a request', function () {
    expect(OwnedNote::forUser($this->bob)->pluck('body')->all())->toBe(['bob note']);
});

test('new records are assigned to the authenticated user', function () {
    $this->actingAs($this->alice);

    $note = OwnedNote::create(['body' => 'new']);

    expect($note->user_id)->toBe($this->alice->id)
        ->and($note->user->is($this->alice))->toBeTrue();
});

test('creating a record without an owner fails', function () {
    OwnedNote::create(['body' => 'orphan']);
})->throws(LogicException::class);

test('user_id cannot be mass assigned', function () {
    $this->actingAs($this->alice);

    $note = OwnedNote::create(['body' => 'new', 'user_id' => $this->bob->id]);

    expect($note->user_id)->toBe($this->alice->id);
});

test('records get a public ulid used as route key', function () {
    $note = OwnedNote::forUser($this->alice)->firstOrFail();

    expect($note->ulid)->toMatch('/^[0-9a-hjkmnp-tv-z]{26}$/')
        ->and($note->getRouteKey())->toBe($note->ulid);
});

test('route model binding resolves only records of the authenticated user', function () {
    Route::middleware(['web', 'auth'])
        ->get('/_test/notes/{note}', fn (OwnedNote $note) => $note->body);

    $aliceNote = OwnedNote::forUser($this->alice)->firstOrFail();
    $bobNote = OwnedNote::forUser($this->bob)->firstOrFail();

    $this->actingAs($this->alice)->get("/_test/notes/{$aliceNote->ulid}")->assertOk()->assertSee('alice note');
    $this->actingAs($this->alice)->get("/_test/notes/{$bobNote->ulid}")->assertNotFound();
    $this->actingAs($this->alice)->get("/_test/notes/{$bobNote->id}")->assertNotFound();
});
