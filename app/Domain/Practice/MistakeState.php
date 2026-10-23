<?php

namespace App\Domain\Practice;

use Carbon\CarbonImmutable;

/**
 * Estado de um erro do caderno, sem nada do banco (ERR-02, ERR-03).
 */
final readonly class MistakeState
{
    public function __construct(
        public int $repetitions = 0,
        public int $step = 0,
        public int $correctStreak = 0,
        public bool $priority = false,
        public ?CarbonImmutable $dueAt = null,
        public bool $resolved = false,
    ) {}

    public static function new(): self
    {
        return new self;
    }
}
