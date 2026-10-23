<?php

namespace App\Domain\Lessons;

use RuntimeException;

/**
 * A aula não pode seguir. O motivo é um código, nunca conteúdo (IAS-04):
 * virus_found, unreadable_file, illegible, too_short.
 */
class LessonRejected extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct("Lesson rejected: {$reason}.");
    }
}
