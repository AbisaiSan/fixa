<?php

namespace App\Domain\Practice;

/**
 * Resultado de um item (ATV-03): total, parcial (erro pequeno) ou erro.
 * NoMatch é só do gabarito: a resposta não bateu e pode ir para a IA.
 */
enum Verdict: string
{
    case Total = 'total';
    case Partial = 'partial';
    case Wrong = 'wrong';
    case NoMatch = 'no_match';
}
