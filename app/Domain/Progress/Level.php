<?php

namespace App\Domain\Progress;

/**
 * Nível pelo XP total (J5-B7, GAM-04): um a cada 400 XP, como no protótipo,
 * com a barra até o próximo.
 */
final readonly class Level
{
    public const XP_PER_LEVEL = 400;

    public function __construct(
        public int $level,
        public int $inLevel,
        public int $toNext,
        public int $percent,
    ) {}

    public static function of(int $xp): self
    {
        $xp = max(0, $xp);
        $inLevel = $xp % self::XP_PER_LEVEL;

        return new self(
            level: intdiv($xp, self::XP_PER_LEVEL) + 1,
            inLevel: $inLevel,
            toNext: self::XP_PER_LEVEL - $inLevel,
            percent: intdiv($inLevel * 100, self::XP_PER_LEVEL),
        );
    }
}
