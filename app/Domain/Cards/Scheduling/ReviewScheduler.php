<?php

namespace App\Domain\Cards\Scheduling;

use Carbon\CarbonImmutable;

/**
 * Agendador de revisões (J3-B3, FLA-03). A interface permite trocar o
 * algoritmo (por exemplo, pelo FSRS) sem mudar quem o usa.
 */
interface ReviewScheduler
{
    /**
     * Próximo estado do cartão depois da nota. O dia é o do fuso do aluno (GAM-06).
     */
    public function schedule(CardState $state, Rating $rating, CarbonImmutable $now, string $timezone): CardState;

    /**
     * Intervalo em dias que cada botão daria, para mostrar no RateBar.
     * Zero quer dizer que o cartão volta ainda na sessão.
     *
     * @return array<int, int> nota => dias
     */
    public function preview(CardState $state, CarbonImmutable $now, string $timezone): array;
}
