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
    case Reading = 'reading';
    case TextCorrection = 'text_correction';
    case WordDetails = 'word_details';

    /**
     * Revisões usam o modelo mais forte; correções curtas, o leve.
     */
    /**
     * Pedidas pelo aluno a qualquer momento, contam no limite diário (ATV-05).
     */
    public function countsTowardsDailyLimit(): bool
    {
        return in_array($this, [self::TextCorrection, self::WordDetails], true);
    }

    public function tier(): ModelTier
    {
        return match ($this) {
            self::TextCorrection, self::WordDetails => ModelTier::Light,
            default => ModelTier::Strong,
        };
    }
}
