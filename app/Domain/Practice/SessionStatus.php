<?php

namespace App\Domain\Practice;

enum SessionStatus: string
{
    case Ready = 'ready';
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
