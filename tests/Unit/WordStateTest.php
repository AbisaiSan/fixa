<?php

use App\Domain\Cards\Scheduling\Rating;
use App\Domain\Vocabulary\WordState;

test('the word state follows the card performance (VOC-02)', function (int $interval, array $lastRatings, WordState $state) {
    expect(WordState::forCard($interval, $lastRatings))->toBe($state);
})->with([
    'nunca revisada' => [0, [], WordState::New],
    'primeira revisão' => [2, [Rating::Good], WordState::Learning],
    '21 dias sem erro nas 3 últimas' => [21, [Rating::Good, Rating::Hard, Rating::Easy], WordState::Mastered],
    'mais de 21 dias' => [40, [Rating::Good, Rating::Good, Rating::Good, Rating::Again], WordState::Mastered],
    '20 dias ainda aprendendo' => [20, [Rating::Good, Rating::Good, Rating::Good], WordState::Learning],
    'erro entre as 3 últimas' => [25, [Rating::Good, Rating::Again, Rating::Good], WordState::Learning],
    'menos de 3 respostas' => [21, [Rating::Easy, Rating::Easy], WordState::Learning],
]);
