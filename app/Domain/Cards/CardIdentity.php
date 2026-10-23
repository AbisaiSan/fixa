<?php

namespace App\Domain\Cards;

use Illuminate\Support\Str;

/**
 * Identidade do cartão para a deduplicação (FLA-05): mesma frente com o
 * mesmo significado, sem diferenciar maiúsculas, espaços, acentos e
 * pontuação final. Significados diferentes da mesma palavra são cartões
 * diferentes.
 */
final class CardIdentity
{
    public static function key(string $front, string $back): string
    {
        return hash('sha256', self::normalize($front).'|'.self::normalize($back));
    }

    private static function normalize(string $text): string
    {
        $text = Str::of($text)->ascii()->lower()->squish()->value();

        return rtrim($text, ' .,;:!?');
    }
}
