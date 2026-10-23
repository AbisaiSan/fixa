<?php

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Providers\FakeAiProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\Support\AiFixtures;

beforeEach(function () {
    $this->lesson = storage_path('framework/testing/aula.txt');
    File::ensureDirectoryExists(dirname($this->lesson));
    File::put($this->lesson, 'Present perfect: I have worked here since 2022.');
});

afterEach(function () {
    File::delete($this->lesson);
    File::deleteDirectory(storage_path('app/private/ai-compare'));
});

test('it only runs locally', function () {
    $this->artisan('ai:compare', ['file' => $this->lesson])->assertFailed();
});

test('it writes each provider output side by side', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['services.gemini.key' => 'test-key']);
    app(FakeAiProvider::class)->respondWith(AiTask::LessonReview, AiFixtures::lessonReview());
    Http::fake(['*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => json_encode(AiFixtures::lessonReview())]]], 'finishReason' => 'STOP']],
        'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 20],
    ])]);

    $this->artisan('ai:compare', ['file' => $this->lesson, '--with' => ['fake', 'gemini:gemini-teste']])->assertSuccessful();

    $files = collect(File::allFiles(storage_path('app/private/ai-compare')))->map->getFilename()->sort()->values()->all();

    expect($files)->toBe(['fake.json', 'gemini_gemini-teste.json']);
});

test('a provider failure is reported without stopping the others', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['services.gemini.key' => '']);
    app(FakeAiProvider::class)->respondWith(AiTask::LessonReview, AiFixtures::lessonReview());

    $this->artisan('ai:compare', ['file' => $this->lesson, '--with' => ['gemini:x', 'fake']])
        ->expectsOutputToContain('Gemini API key is not configured.')
        ->assertSuccessful();
});
