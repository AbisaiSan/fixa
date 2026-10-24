<?php

namespace App\Domain\Plan;

/**
 * Atividade da aula mais recente no bloco do meio (PLA-02), em rodízio
 * pelos dias da semana nesta ordem.
 */
enum LessonActivity: string
{
    case Writing = 'writing';
    case Reading = 'reading';
    case Vocabulary = 'vocabulary';
}
