<?php

namespace App\Domain\Practice;

/**
 * Categorias do caderno de erros (ERR-01).
 */
enum ErrorCategory: string
{
    case Grammar = 'grammar';
    case Vocabulary = 'vocabulary';
    case Preposition = 'preposition';
    case Spelling = 'spelling';
}
