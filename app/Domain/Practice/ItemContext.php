<?php

namespace App\Domain\Practice;

/**
 * Contexto das frases (ATV-01).
 */
enum ItemContext: string
{
    case Daily = 'daily';
    case Work = 'work';
    case Travel = 'travel';
}
