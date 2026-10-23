<?php

use App\Logging\StructuredLogging;
use App\Models\User;
use App\Support\Audit\AuditLog;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->logDir = storage_path('framework/testing/logs');
    File::deleteDirectory($this->logDir);
});

afterEach(function () {
    File::deleteDirectory($this->logDir);
});

/**
 * @return list<array<string, mixed>>
 */
function readJsonLog(string $pattern): array
{
    $files = glob($pattern) ?: [];

    expect($files)->toHaveCount(1);

    return collect(file($files[0], FILE_IGNORE_NEW_LINES))
        ->map(fn (string $line) => json_decode($line, true, flags: JSON_THROW_ON_ERROR))
        ->all();
}

test('application logs are json lines with request context and no student content', function () {
    Context::add('request_id', '01testrequest');

    config(['logging.channels.single.path' => $this->logDir.'/app.log']);
    Log::forgetChannel('single');

    Log::channel('single')->warning('Lesson processing failed', ['lesson' => 'les_01', 'student_text' => 'I has a dog']);

    [$entry] = readJsonLog($this->logDir.'/app.log');

    expect($entry['message'])->toBe('Lesson processing failed')
        ->and($entry['level_name'])->toBe('WARNING')
        ->and($entry['context'])->toBe(['lesson' => 'les_01', 'student_text' => '[redacted]'])
        ->and($entry['extra']['request_id'] ?? null)->toBe('01testrequest');
});

test('the configured channels use the structured format', function () {
    foreach (['single', 'daily', 'audit'] as $channel) {
        expect(config("logging.channels.{$channel}.tap"))->toContain(StructuredLogging::class);
    }
});

test('audit records identifiers and metadata only', function () {
    config(['logging.channels.audit.path' => $this->logDir.'/audit.log']);
    Log::forgetChannel('audit');

    $user = User::factory()->create(['email' => 'aluno@example.com']);

    app(AuditLog::class)->record('account.exported', $user, ['format' => 'json', 'note' => 'free text']);

    [$entry] = readJsonLog($this->logDir.'/audit-*.log');

    expect($entry['message'])->toBe('account.exported')
        ->and($entry['context']['actor'])->toBe($user->ulid)
        ->and($entry['context']['metadata'])->toBe(['format' => 'json', 'note' => '[redacted]'])
        ->and(json_encode($entry))->not->toContain('aluno@example.com');
});

test('each web response carries a request id', function () {
    $response = $this->get('/entrar');

    expect($response->headers->get('X-Request-Id'))->toMatch('/^[0-9a-z]{26}$/');
});
