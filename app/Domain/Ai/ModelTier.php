<?php

namespace App\Domain\Ai;

enum ModelTier: string
{
    case Strong = 'strong';
    case Light = 'light';
}
