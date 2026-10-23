<?php

namespace App\Domain\Practice;

use App\Models\Mistake;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Caderno de erros (J4-B8, ERR-01). Resposta errada ou parcial abre ou
 * repete o erro do mesmo ponto; o ponto é o que o item treina, na mesma
 * categoria. Guarda o texto do aluno e a correção mais recentes.
 */
final class MistakeBook
{
    public function record(User $user, PracticeItem $item, string $answer, Correction $correction): void
    {
        if ($correction->verdict === Verdict::Total) {
            return;
        }

        $point = $this->point($item, $correction);
        $category = $correction->category ?? ErrorCategory::Grammar;
        $key = self::pointKey($point);
        $now = CarbonImmutable::now();
        $session = PracticeSession::withoutGlobalScopes()->find($item->practice_session_id);

        $content = [
            'point' => $point,
            'answer' => trim($answer),
            'corrected' => $correction->corrected,
            'natural' => $correction->natural,
            'explanation' => $correction->explanation,
        ];

        $mistake = Mistake::forUser($user)->where('category', $category)->where('point_key', $key)->first();

        if ($mistake === null) {
            (new Mistake)->forceFill([
                'user_id' => $user->id,
                'source_lesson_id' => $session?->source_lesson_id,
                'category' => $category,
                'point_key' => $key,
                'content' => $content,
                'repetitions' => 1,
                'last_error_at' => $now,
            ])->save();

            return;
        }

        $mistake->forceFill([
            'content' => $content,
            'repetitions' => $mistake->repetitions + 1,
            'last_error_at' => $now,
            'resolved_at' => null,
        ])->save();
    }

    /**
     * "Mesmo ponto": o ponto normalizado vira uma chave sem texto do aluno.
     */
    public static function pointKey(string $point): string
    {
        return hash('sha256', AnswerNormalizer::normalize($point));
    }

    private function point(PracticeItem $item, Correction $correction): string
    {
        $point = $correction->point ?? $item->content['point'] ?? null;

        return is_string($point) && trim($point) !== '' ? trim($point) : $correction->corrected;
    }
}
