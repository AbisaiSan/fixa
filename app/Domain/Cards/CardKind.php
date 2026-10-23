<?php

namespace App\Domain\Cards;

/**
 * O que está na frente do cartão (FLA-01, FLA-02): palavra ou frase-chave.
 */
enum CardKind: string
{
    case Word = 'word';
    case Phrase = 'phrase';
}
