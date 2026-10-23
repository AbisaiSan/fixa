<?php

namespace App\Domain\Lessons;

/**
 * Formatos aceitos no MVP (MAT-02).
 */
enum LessonSource: string
{
    case Text = 'text';
    case Pdf = 'pdf';
    case Image = 'image';
}
