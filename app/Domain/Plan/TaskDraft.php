<?php

namespace App\Domain\Plan;

final readonly class TaskDraft
{
    public function __construct(
        public TaskKind $kind,
        public int $minutes,
        public bool $important,
        public ?LessonActivity $activity = null,
        public ?int $lessonId = null,
    ) {}
}
