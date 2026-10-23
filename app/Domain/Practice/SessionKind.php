<?php

namespace App\Domain\Practice;

/**
 * Tipos de sessão de prática (ATV-01, ATV-04, PLA-02).
 */
enum SessionKind: string
{
    case Writing = 'writing';
    case Reading = 'reading';
    case WeakPoint = 'weak_point';
}
