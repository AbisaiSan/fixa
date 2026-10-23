<?php

namespace App\Domain\Vocabulary;

/**
 * De onde veio a palavra (VOC-01, VOC-03).
 */
enum WordOrigin: string
{
    case Lesson = 'lesson';
    case Mine = 'mine';
}
