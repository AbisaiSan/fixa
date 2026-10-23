<?php

namespace App\Domain\Lessons;

/**
 * Formatos aceitos no MVP (MAT-02): texto colado e PDF com texto.
 */
enum LessonSource: string
{
    case Text = 'text';
    case Pdf = 'pdf';
}
