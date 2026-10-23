<?php

namespace App\Domain\Practice;

use App\Models\Enrollment;
use App\Models\Mistake;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Caderno de erros (J4-B8, J4-B9, ERR-01 a ERR-03). Resposta errada ou
 * parcial abre ou repete o erro do mesmo ponto; o ponto é o que o item
 * treina, na mesma categoria. Guarda o texto do aluno e a correção mais
 * recentes. Acerto no mesmo ponto avança a agenda até o erro sair da lista.
 */
final class MistakeBook
{
    public function record(User $user, PracticeItem $item, string $answer, Correction $correction): void
    {
        $now = CarbonImmutable::now();
        $timezone = $user->activeEnrollment->timezone ?? Enrollment::DEFAULT_TIMEZONE;

        if ($correction->verdict === Verdict::Total) {
            $this->recordCorrect($user, $item, $now, $timezone);

            return;
        }

        $point = $this->point($item, $correction);
        $category = $correction->category ?? ErrorCategory::Grammar;
        $key = self::pointKey($point);
        $session = PracticeSession::withoutGlobalScopes()->find($item->practice_session_id);

        $content = [
            'point' => $point,
            'answer' => trim($answer),
            'corrected' => $correction->corrected,
            'natural' => $correction->natural,
            'explanation' => $correction->explanation,
        ];

        $mistake = Mistake::forUser($user)->where('category', $category)->where('point_key', $key)->first()
            ?? (new Mistake)->forceFill([
                'user_id' => $user->id,
                'source_lesson_id' => $session?->source_lesson_id,
                'category' => $category,
                'point_key' => $key,
                'repetitions' => 0,
            ]);

        $next = MistakeSchedule::afterError(self::stateOf($mistake), $now, $timezone);

        $mistake->forceFill([
            ...self::fill($next, $now),
            'content' => $content,
            'last_error_at' => $now,
        ])->save();
    }

    /**
     * Acerto no ponto de um erro aberto, como no ponto fraco (ERR-03).
     */
    private function recordCorrect(User $user, PracticeItem $item, CarbonImmutable $now, string $timezone): void
    {
        $point = $item->content['point'] ?? null;

        if (! is_string($point) || trim($point) === '') {
            return;
        }

        $mistakes = Mistake::forUser($user)->open()->where('point_key', self::pointKey($point))->get();

        foreach ($mistakes as $mistake) {
            $mistake->forceFill(self::fill(MistakeSchedule::afterCorrect(self::stateOf($mistake), $now, $timezone), $now))->save();
        }
    }

    public static function stateOf(Mistake $mistake): MistakeState
    {
        return new MistakeState(
            repetitions: $mistake->repetitions,
            step: $mistake->step ?? 0,
            correctStreak: $mistake->correct_streak ?? 0,
            priority: (bool) $mistake->priority,
            dueAt: $mistake->due_at === null ? null : CarbonImmutable::instance($mistake->due_at),
            resolved: $mistake->resolved_at !== null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function fill(MistakeState $state, CarbonImmutable $now): array
    {
        return [
            'repetitions' => $state->repetitions,
            'step' => $state->step,
            'correct_streak' => $state->correctStreak,
            'priority' => $state->priority,
            'due_at' => $state->dueAt,
            'resolved_at' => $state->resolved ? $now : null,
        ];
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
