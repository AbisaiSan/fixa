<?php

namespace App\Domain\Lessons;

/**
 * Máquina de estados da aula (J2-B2, MAT-01).
 */
enum LessonStatus: string
{
    case Queued = 'queued';
    case Extracting = 'extracting';
    case Generating = 'generating';
    case Validating = 'validating';
    case Ready = 'ready';
    case Failed = 'failed';

    /**
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Queued => [self::Extracting, self::Failed],
            self::Extracting => [self::Generating, self::Failed],
            self::Generating => [self::Validating, self::Failed],
            // Saída inválida volta a gerar uma vez antes de falhar (IAS-02).
            self::Validating => [self::Ready, self::Generating, self::Failed],
            // Regenerar só por pedido do aluno (REV-04).
            self::Ready => [self::Generating],
            // "Tentar de novo" recomeça o processamento.
            self::Failed => [self::Queued],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    /**
     * Ainda em processamento: a tela acompanha por polling (J2-B11).
     */
    public function isProcessing(): bool
    {
        return in_array($this, [self::Queued, self::Extracting, self::Generating, self::Validating], true);
    }
}
