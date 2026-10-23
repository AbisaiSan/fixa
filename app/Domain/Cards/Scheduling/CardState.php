<?php

namespace App\Domain\Cards\Scheduling;

use Carbon\CarbonImmutable;

/**
 * Estado de repetição espaçada de um cartão, sem nada do banco.
 */
final readonly class CardState
{
    public const INITIAL_EASE = 2.5;

    public function __construct(
        public int $repetitions = 0,
        public int $intervalDays = 0,
        public float $ease = self::INITIAL_EASE,
        public int $lapses = 0,
        public ?CarbonImmutable $dueAt = null,
    ) {}

    public static function new(): self
    {
        return new self;
    }

    public function isNew(): bool
    {
        return $this->repetitions === 0 && $this->lapses === 0 && $this->dueAt === null;
    }
}
