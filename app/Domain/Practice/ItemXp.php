<?php

namespace App\Domain\Practice;

/**
 * XP por item, proporcional ao resultado (ATV-03, GAM-03), com os valores
 * do protótipo. Tentar sempre vale alguma coisa: o erro também ganha XP.
 */
final class ItemXp
{
    public static function for(ItemType $type, Verdict $verdict): int
    {
        if ($type === ItemType::Choice) {
            return $verdict === Verdict::Total ? 4 : 1;
        }

        return match ($verdict) {
            Verdict::Total => 10,
            Verdict::Partial => 6,
            Verdict::Wrong, Verdict::NoMatch => 2,
        };
    }
}
