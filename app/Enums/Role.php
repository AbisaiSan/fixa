<?php

namespace App\Enums;

/**
 * Papéis de acesso (J1-B5). O professor entra na jornada 8.
 */
enum Role: string
{
    case Student = 'student';
    case Admin = 'admin';
}
