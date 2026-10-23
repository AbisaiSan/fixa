<?php

namespace App\Domain\Practice;

use App\Models\Attempt;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Registra a resposta a um item (J4-B4, ATV-03): corrige, dá o resultado e o
 * XP proporcional e avança o estado da sessão. Reenviar a resposta de um
 * item já respondido devolve a mesma correção, sem custo de IA.
 */
final class AnswerItem
{
    public function __construct(private ItemCorrector $corrector, private MistakeBook $mistakes) {}

    /**
     * @throws DailyLimitReached
     * @throws CorrectionUnavailable
     */
    public function handle(User $user, PracticeItem $item, string $answer): Attempt
    {
        $existing = Attempt::forUser($user)->where('practice_item_id', $item->id)->first();

        if ($existing !== null) {
            return $existing;
        }

        // A correção (talvez com IA) fica fora da transação, que só grava.
        $correction = $this->corrector->correct($user, $item, $answer);
        $now = CarbonImmutable::now();

        return DB::transaction(function () use ($user, $item, $answer, $correction, $now) {
            $attempt = (new Attempt)->forceFill([
                'user_id' => $user->id,
                'practice_item_id' => $item->id,
                'answer' => trim($answer),
                'correction' => $correction->toArray(),
                'verdict' => $correction->verdict,
                'source' => $correction->source,
                'xp' => ItemXp::for($item->type, $correction->verdict),
                'answered_at' => $now,
            ]);
            $attempt->save();

            $this->advance($item, $now);
            // Erros vão para o caderno (ERR-01).
            $this->mistakes->record($user, $item, $answer, $correction);

            return $attempt;
        });
    }

    private function advance(PracticeItem $item, CarbonImmutable $now): void
    {
        $session = PracticeSession::withoutGlobalScopes()->lockForUpdate()->findOrFail($item->practice_session_id);
        $answered = Attempt::withoutGlobalScopes()
            ->whereIn('practice_item_id', $session->items()->select('id'))
            ->count();

        $session->forceFill([
            'started_at' => $session->started_at ?? $now,
            'status' => $answered >= $session->items()->count() ? SessionStatus::Completed : SessionStatus::InProgress,
            'completed_at' => $answered >= $session->items()->count() ? ($session->completed_at ?? $now) : null,
        ])->save();
    }
}
