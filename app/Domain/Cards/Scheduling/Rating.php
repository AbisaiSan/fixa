<?php

namespace App\Domain\Cards\Scheduling;

/**
 * Os quatro botões depois de cada cartão (FLA-03), com as teclas 1 a 4.
 */
enum Rating: int
{
    case Again = 1;
    case Hard = 2;
    case Good = 3;
    case Easy = 4;
}
