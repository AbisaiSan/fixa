<?php

namespace App\Domain\Lessons;

/**
 * Conta palavras em qualquer idioma: sequências de letras e números.
 */
final class WordCounter
{
    public static function count(?string $text): int
    {
        return $text === null ? 0 : (int) preg_match_all("/[\\p{L}\\p{N}][\\p{L}\\p{N}'’-]*/u", $text);
    }
}
