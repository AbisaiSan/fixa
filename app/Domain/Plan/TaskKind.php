<?php

namespace App\Domain\Plan;

/**
 * Os 3 blocos do dia (PLA-02).
 */
enum TaskKind: string
{
    case Flashcards = 'flashcards';
    case Lesson = 'lesson';
    case WeakPoint = 'weak_point';

    /**
     * Peso na divisão da meta diária: 3, 4 e 3 dão 3, 4 e 3 minutos na meta de 10.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Flashcards, self::WeakPoint => 3,
            self::Lesson => 4,
        };
    }

    /**
     * Tarefa importante é remanejada quando o aluno falta (PLA-03). Os
     * flashcards vencidos se refazem sozinhos na fila do dia seguinte.
     */
    public function important(): bool
    {
        return $this !== self::Flashcards;
    }
}
