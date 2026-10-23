<?php

namespace App\Domain\Lessons;

use LogicException;

class InvalidLessonTransition extends LogicException
{
    public function __construct(LessonStatus $from, LessonStatus $to)
    {
        parent::__construct("A lesson cannot go from {$from->value} to {$to->value}.");
    }
}
