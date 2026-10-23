<?php

namespace App\Domain\Practice;

use App\Models\Mistake;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Atividade de ponto fraco (J4-B10, PLA-02), montada do caderno de erros
 * sem IA: cada erro que voltou vira uma escolha entre a versão corrigida e o
 * que o aluno escreveu, com gabarito fixo. Erros com prioridade vêm antes,
 * depois os mais atrasados (ERR-02). Acertar o ponto avança o erro (ERR-03).
 */
final class WeakPointBuilder
{
    public const MIN_ITEMS = 3;

    public const MAX_ITEMS = 5;

    /**
     * Sessão aberta de ponto fraco ou uma nova. Nulo quando nada voltou hoje.
     */
    public function for(User $user): ?PracticeSession
    {
        $open = PracticeSession::forUser($user)
            ->where('kind', SessionKind::WeakPoint)
            ->whereIn('status', [SessionStatus::Ready, SessionStatus::InProgress])
            ->latest('id')
            ->first();

        if ($open !== null) {
            return $open;
        }

        $mistakes = $this->due($user);

        if ($mistakes->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($user, $mistakes) {
            $session = (new PracticeSession)->forceFill([
                'user_id' => $user->id,
                'source_lesson_id' => null,
                'kind' => SessionKind::WeakPoint,
                'status' => SessionStatus::Ready,
            ]);
            $session->save();

            foreach ($mistakes->values() as $position => $mistake) {
                $content = $mistake->content;
                $options = [$content['corrected'], $content['answer']];
                // A ordem das opções muda, para a certa não ficar sempre em cima.
                if ($position % 2 === 1) {
                    $options = array_reverse($options);
                }

                (new PracticeItem)->forceFill([
                    'user_id' => $user->id,
                    'practice_session_id' => $session->id,
                    'position' => $position + 1,
                    'type' => ItemType::Choice,
                    'content' => [
                        'instruction' => 'Escolha a opção correta.',
                        'sentence' => $content['point'],
                        'options' => $options,
                        'answer' => array_search($content['corrected'], $options, true),
                        'accepted' => [$content['corrected']],
                        'corrected' => $content['corrected'],
                        'natural' => $content['natural'] ?? $content['corrected'],
                        'why' => $content['explanation'],
                        'point' => $content['point'],
                        'category' => $mistake->category->value,
                        'mistake' => $mistake->ulid,
                    ],
                ])->save();
            }

            return $session;
        });
    }

    /**
     * Erros abertos que já voltaram, prioridade primeiro. Sem a resposta do
     * aluno diferente da corrigida, a escolha não faria sentido.
     *
     * @return Collection<int, Mistake>
     */
    private function due(User $user): Collection
    {
        return Mistake::forUser($user)
            ->open()
            ->where('due_at', '<=', CarbonImmutable::now())
            ->orderByDesc('priority')
            ->orderBy('due_at')
            ->limit(self::MAX_ITEMS * 2)
            ->get()
            ->filter(fn (Mistake $mistake) => AnswerNormalizer::normalize($mistake->content['answer']) !== AnswerNormalizer::normalize($mistake->content['corrected']))
            ->take(self::MAX_ITEMS)
            ->values();
    }
}
