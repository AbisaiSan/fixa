<?php

use App\Logging\RedactSensitiveData;
use Illuminate\Database\QueryException;
use Monolog\Level;
use Monolog\LogRecord;

function redact(array $context, array $extra = []): LogRecord
{
    $record = new LogRecord(new DateTimeImmutable, 'test', Level::Info, 'message', $context, $extra);

    return (new RedactSensitiveData)($record);
}

test('sensitive keys are redacted at any depth', function () {
    $record = redact([
        'password' => 'hunter2',
        'accessToken' => 'abc',
        'X-Api-Key' => 'key',
        'student_text' => 'I have went to London',
        'lesson' => ['id' => 10, 'material' => 'present perfect notes', 'items' => [['answer' => 'gone']]],
        'user_email' => 'aluno@example.com',
    ], ['prompt' => 'translate this']);

    expect($record->context)->toBe([
        'password' => '[redacted]',
        'accessToken' => '[redacted]',
        'X-Api-Key' => '[redacted]',
        'student_text' => '[redacted]',
        'lesson' => ['id' => 10, 'material' => '[redacted]', 'items' => [['answer' => '[redacted]']]],
        'user_email' => '[redacted]',
    ])->and($record->extra)->toBe(['prompt' => '[redacted]']);
});

test('harmless keys are kept', function () {
    $context = ['tokens_used' => 120, 'context_size' => 3, 'request_id' => '01abc', 'duration_ms' => 40];

    expect(redact($context)->context)->toBe($context);
});

test('exceptions keep only class and location, never the message', function () {
    $record = redact(['exception' => new RuntimeException('student wrote: secret')]);

    expect($record->context['exception'])
        ->toHaveKeys(['class', 'code', 'file'])
        ->and(json_encode($record->context))->not->toContain('secret');
});

test('query exceptions keep the sql without bound values', function () {
    $exception = new QueryException('pgsql', 'insert into notes (body) values (?)', ['my private note'], new PDOException('fail'));

    $record = redact(['exception' => $exception]);

    expect($record->context['exception']['sql'])->toBe('insert into notes (body) values (?)')
        ->and(json_encode($record->context))->not->toContain('my private note');
});
