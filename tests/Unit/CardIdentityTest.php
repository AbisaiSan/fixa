<?php

use App\Domain\Cards\CardIdentity;

test('the same word with the same meaning has the same key', function (string $front, string $back) {
    expect(CardIdentity::key($front, $back))->toBe(CardIdentity::key('deadline', 'prazo final'));
})->with([
    'igual' => ['deadline', 'prazo final'],
    'maiúsculas e espaços' => ['  Deadline ', 'Prazo   final'],
    'pontuação no fim' => ['deadline.', 'prazo final!'],
    'acentos no significado' => ['deadline', 'prazó final'],
]);

test('a different meaning is a different card', function () {
    expect(CardIdentity::key('bank', 'banco (instituição)'))
        ->not->toBe(CardIdentity::key('bank', 'margem do rio'));
});

test('a different word is a different card', function () {
    expect(CardIdentity::key('deadline', 'prazo final'))->not->toBe(CardIdentity::key('due date', 'prazo final'));
});
