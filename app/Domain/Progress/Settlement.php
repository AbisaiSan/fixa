<?php

namespace App\Domain\Progress;

final readonly class Settlement
{
    /**
     * @param  list<string>  $frozen  dias protegidos por congelamento
     */
    public function __construct(
        public StreakState $state,
        public array $frozen,
    ) {}
}
