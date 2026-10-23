<?php

use App\Domain\Ai\AiImage;
use App\Domain\Ai\AiRequest;
use App\Domain\Ai\AiTask;
use App\Domain\Ai\Contracts\AiProvider;
use App\Domain\Ai\Exceptions\AiProviderException;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Ai\Providers\GeminiProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'fixa.ai.provider' => 'gemini',
        'services.gemini.key' => 'test-key',
        'services.gemini.models.strong' => 'gemini-strong',
        'services.gemini.models.light' => 'gemini-light',
    ]);
    app()->forgetInstance(AiProvider::class);
});

function geminiRequest(AiTask $task = AiTask::LessonReview, array $images = []): AiRequest
{
    return new AiRequest(
        task: $task,
        promptVersion: 'v1',
        instructions: 'Instruções do sistema.',
        material: '<material>texto da aula</material>',
        schema: ['type' => 'OBJECT', 'properties' => ['summary' => ['type' => 'STRING']]],
        images: $images,
    );
}

function geminiAnswer(array $data, string $finish = 'STOP'): array
{
    return [
        'candidates' => [['content' => ['parts' => [['text' => json_encode($data)]]], 'finishReason' => $finish]],
        'usageMetadata' => ['promptTokenCount' => 120, 'candidatesTokenCount' => 40, 'thoughtsTokenCount' => 10],
    ];
}

test('the configured provider is gemini', function () {
    expect(app(AiProvider::class))->toBeInstanceOf(GeminiProvider::class);
});

test('it sends instructions, delimited material and schema, without tools', function () {
    Http::fake(['*' => Http::response(geminiAnswer(['summary' => 'ok']))]);

    $response = app(AiProvider::class)->generate(geminiRequest());

    expect($response->data)->toBe(['summary' => 'ok'])
        ->and($response->provider)->toBe('gemini')
        ->and($response->model)->toBe('gemini-strong')
        ->and($response->inputTokens)->toBe(120)
        ->and($response->outputTokens)->toBe(50);

    Http::assertSent(function (Request $request) {
        $body = $request->data();

        return str_ends_with($request->url(), '/models/gemini-strong:generateContent')
            && $request->header('x-goog-api-key') === ['test-key']
            && ! str_contains($request->url(), 'test-key')
            && $body['systemInstruction']['parts'][0]['text'] === 'Instruções do sistema.'
            && $body['contents'][0]['parts'][0]['text'] === '<material>texto da aula</material>'
            && $body['generationConfig']['responseMimeType'] === 'application/json'
            && isset($body['generationConfig']['responseSchema'])
            && ! array_key_exists('tools', $body);
    });
});

test('light tasks use the light model', function () {
    Http::fake(['*' => Http::response(geminiAnswer(['ok' => true]))]);

    $response = app(AiProvider::class)->generate(geminiRequest(AiTask::TextCorrection));

    expect($response->model)->toBe('gemini-light');
});

test('images go inline next to the material', function () {
    Http::fake(['*' => Http::response(geminiAnswer(['text' => 'lido']))]);

    app(AiProvider::class)->generate(geminiRequest(AiTask::TextExtraction, [new AiImage('image/jpeg', 'YWJj')]));

    Http::assertSent(fn (Request $request) => $request->data()['contents'][0]['parts'][1] === [
        'inline_data' => ['mime_type' => 'image/jpeg', 'data' => 'YWJj'],
    ]);
});

test('failures become provider exceptions without student text', function (array|string $fake, bool $retryable) {
    Http::fake(['*' => is_array($fake) ? Http::response($fake) : Http::response('', (int) $fake)]);

    try {
        app(AiProvider::class)->generate(geminiRequest());
        $this->fail('Expected an AiProviderException.');
    } catch (AiProviderException $exception) {
        expect($exception->retryable)->toBe($retryable)
            ->and($exception->getMessage())->not->toContain('texto da aula');
    }
})->with([
    'limite de uso' => ['429', true],
    'erro do servidor' => ['503', true],
    'pedido inválido' => ['400', false],
    'bloqueio de segurança' => [fn () => geminiAnswer([], 'SAFETY'), false],
    'texto que não é JSON' => [fn () => ['candidates' => [['content' => ['parts' => [['text' => 'não é json']]], 'finishReason' => 'STOP']]], true],
]);

test('a missing key fails before any request', function () {
    config(['services.gemini.key' => '']);
    app()->forgetInstance(AiProvider::class);
    Http::fake();

    expect(fn () => app(AiProvider::class)->generate(geminiRequest()))->toThrow(AiProviderException::class);
    Http::assertNothingSent();
});

test('tests use the fake provider by default', function () {
    config(['fixa.ai.provider' => 'fake']);
    app()->forgetInstance(AiProvider::class);

    $fake = app(FakeAiProvider::class)->respondWith(AiTask::LessonReview, ['summary' => 'falso']);

    expect(app(AiProvider::class)->generate(geminiRequest())->data)->toBe(['summary' => 'falso']);
    $fake->assertSentCount(1, AiTask::LessonReview);
});
