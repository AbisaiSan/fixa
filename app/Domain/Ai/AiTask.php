<?php

namespace App\Domain\Ai;

/**
 * Tarefas que a IA executa (Estratégia de IA). Cada uma tem prompt
 * versionado e esquema de saída próprios.
 */
enum AiTask: string
{
    case LessonReview = 'lesson_review';
    case Cards = 'cards';
    case Activities = 'activities';
    case TextCorrection = 'text_correction';

    /**
     * Revisões usam o modelo mais forte; correções curtas, o leve.
     */
    public function tier(): ModelTier
    {
        return match ($this) {
            self::TextCorrection => ModelTier::Light,
            default => ModelTier::Strong,
        };
    }
}
