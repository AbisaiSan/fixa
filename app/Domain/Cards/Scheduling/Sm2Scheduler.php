<?php

namespace App\Domain\Cards\Scheduling;

use Carbon\CarbonImmutable;

/**
 * SM-2 adaptado (FLA-03), aberto e documentado aqui.
 *
 * Cada cartão tem repetições seguidas (n), intervalo em dias (I) e fator de
 * facilidade (F, começa em 2,5 e nunca fica abaixo de 1,3).
 *
 * - Errei: reinicia (n = 0, I = 0), soma um lapso, F cai 0,20 e o cartão
 *   volta em 1 minuto, ainda na sessão.
 * - Difícil, Bom e Fácil mudam F em -0,15, 0 e +0,15 e somam uma repetição.
 * - Intervalo do Bom: 2 dias no primeiro acerto, 6 no segundo e, depois,
 *   I x F. Fácil dá pelo menos 30% a mais que o Bom (4 dias no primeiro
 *   acerto). Difícil dá 20% a mais que I (1 dia no primeiro acerto), sempre
 *   abaixo do Bom.
 * - Nenhum intervalo passa de 365 dias.
 *
 * O vencimento é o início do dia, no fuso do aluno: revisar às 23h50 ou às
 * 0h10 muda o dia em que o cartão volta, como o aluno espera (GAM-06).
 */
final class Sm2Scheduler implements ReviewScheduler
{
    public const MIN_EASE = 1.3;

    public const MAX_INTERVAL = 365;

    private const EASE_CHANGE = [
        Rating::Again->value => -0.20,
        Rating::Hard->value => -0.15,
        Rating::Good->value => 0.0,
        Rating::Easy->value => 0.15,
    ];

    public function schedule(CardState $state, Rating $rating, CarbonImmutable $now, string $timezone): CardState
    {
        $ease = $this->ease($state, $rating);

        if ($rating === Rating::Again) {
            return new CardState(
                repetitions: 0,
                intervalDays: 0,
                ease: $ease,
                lapses: $state->lapses + 1,
                dueAt: $now->addMinute(),
            );
        }

        $interval = $this->interval($state, $rating);

        return new CardState(
            repetitions: $state->repetitions + 1,
            intervalDays: $interval,
            ease: $ease,
            lapses: $state->lapses,
            dueAt: $now->setTimezone($timezone)->startOfDay()->addDays($interval)->utc(),
        );
    }

    public function preview(CardState $state, CarbonImmutable $now, string $timezone): array
    {
        $preview = [];

        foreach (Rating::cases() as $rating) {
            $preview[$rating->value] = $rating === Rating::Again ? 0 : $this->interval($state, $rating);
        }

        return $preview;
    }

    private function ease(CardState $state, Rating $rating): float
    {
        return round(max(self::MIN_EASE, $state->ease + self::EASE_CHANGE[$rating->value]), 2);
    }

    private function interval(CardState $state, Rating $rating): int
    {
        $ease = $this->ease($state, $rating);

        $interval = match ($rating) {
            Rating::Hard => $this->hard($state),
            Rating::Good => $this->good($state, $state->ease),
            Rating::Easy => $this->easy($state, $ease),
            Rating::Again => 0,
        };

        return min(self::MAX_INTERVAL, max(1, $interval));
    }

    private function good(CardState $state, float $ease): int
    {
        return match ($state->repetitions) {
            0 => 2,
            1 => 6,
            default => (int) round($state->intervalDays * $ease),
        };
    }

    private function hard(CardState $state): int
    {
        if ($state->repetitions === 0) {
            return 1;
        }

        $hard = max($state->intervalDays + 1, (int) round($state->intervalDays * 1.2));

        return min($hard, $this->good($state, $state->ease) - 1);
    }

    private function easy(CardState $state, float $ease): int
    {
        if ($state->repetitions === 0) {
            return 4;
        }

        $good = $this->good($state, $ease);

        return max($good + 1, (int) round($good * 1.3));
    }
}
