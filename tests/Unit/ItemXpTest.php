<?php

use App\Domain\Practice\ItemType;
use App\Domain\Practice\ItemXp;
use App\Domain\Practice\Verdict;

test('XP follows the result of the item (ATV-03, GAM-03)', function (ItemType $type, Verdict $verdict, int $xp) {
    expect(ItemXp::for($type, $verdict))->toBe($xp);
})->with([
    'escrita, total' => [ItemType::Translate, Verdict::Total, 10],
    'escrita, parcial' => [ItemType::Fill, Verdict::Partial, 6],
    'escrita, erro' => [ItemType::Context, Verdict::Wrong, 2],
    'escolha, certa' => [ItemType::Choice, Verdict::Total, 4],
    'escolha, errada' => [ItemType::Choice, Verdict::Wrong, 1],
]);

test('XP is never negative and never zero for an answer', function () {
    foreach (ItemType::cases() as $type) {
        foreach ([Verdict::Total, Verdict::Partial, Verdict::Wrong] as $verdict) {
            expect(ItemXp::for($type, $verdict))->toBeGreaterThan(0);
        }
    }
});
