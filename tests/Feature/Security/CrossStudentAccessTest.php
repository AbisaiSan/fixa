<?php

use App\Models\Concerns\BelongsToUser;
use App\Models\User;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\URL;
use Tests\Support\OwnedEndpoints;

beforeEach(function () {
    OwnedEndpoints::bootFixtures();

    $this->alice = User::factory()->create();
    $this->bob = User::factory()->create();
});

dataset('owned endpoints', fn () => array_map(fn (array $endpoint) => [$endpoint], OwnedEndpoints::all()));

function endpointUrl(array $endpoint, array $params): string
{
    return $endpoint['signed']
        ? URL::temporarySignedRoute($endpoint['route'], now()->addMinutes(5), $params)
        : route($endpoint['route'], $params);
}

test('another student cannot read, change or delete the data', function (array $endpoint) {
    ['resource' => $resource, 'params' => $params] = ($endpoint['make'])($this->alice);
    $before = $resource->newQueryWithoutScopes()->find($resource->getKey())->getAttributes();

    $response = $this->actingAs($this->bob)->{$endpoint['method']}(endpointUrl($endpoint, $params), $endpoint['payload']);

    expect($response->status())->toBeIn([403, 404])
        ->and($response->getContent())->not->toContain('nota da aluna A');

    $after = $resource->newQueryWithoutScopes()->find($resource->getKey());

    expect($after)->not->toBeNull('o dado do aluno A foi apagado')
        ->and($after->getAttributes())->toBe($before);
})->with('owned endpoints');

test('the owner still has access, so the denial above is real', function (array $endpoint) {
    ['params' => $params] = ($endpoint['make'])($this->alice);

    $response = $this->actingAs($this->alice)
        ->{$endpoint['method']}(endpointUrl($endpoint, $params), $endpoint['payload']);

    // Ações de formulário respondem com redirect; sem erro de validação, valeu.
    expect($response->getStatusCode())->toBeLessThan(400);
    $response->assertSessionHasNoErrors();
})->with('owned endpoints');

test('the numeric id never works as a route key', function (array $endpoint) {
    ['numeric' => $numeric] = ($endpoint['make'])($this->alice);

    $this->actingAs($this->alice)
        ->{$endpoint['method']}(endpointUrl($endpoint, $numeric), $endpoint['payload'])
        ->assertNotFound();
})->with('owned endpoints');

test('every route that receives student data is in the matrix', function () {
    $covered = OwnedEndpoints::routeNames();

    $missing = collect(app('router')->getRoutes()->getRoutes())
        ->filter(function (Route $route) {
            return collect($route->signatureParameters())->contains(function (ReflectionParameter $parameter) {
                $type = $parameter->getType();
                $class = $type instanceof ReflectionNamedType && ! $type->isBuiltin() ? $type->getName() : null;

                return $class !== null && in_array(BelongsToUser::class, class_uses_recursive($class), true);
            });
        })
        ->map(fn (Route $route) => $route->getName() ?? $route->uri())
        ->reject(fn (string $name) => in_array($name, $covered, true))
        ->values()
        ->all();

    expect($missing)->toBe([], 'Rotas com dado de aluno fora de tests/Support/OwnedEndpoints.php: '.implode(', ', $missing));
});
