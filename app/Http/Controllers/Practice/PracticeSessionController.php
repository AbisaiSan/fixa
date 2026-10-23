<?php

namespace App\Http\Controllers\Practice;

use App\Domain\Practice\ItemType;
use App\Domain\Practice\SessionKind;
use App\Domain\Practice\WeakPointBuilder;
use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sessões de escrita e de ponto fraco em modo foco (J4-F2, J4-F7). O
 * gabarito e a correção só vão para a tela depois da resposta.
 */
class PracticeSessionController extends Controller
{
    public function writing(PracticeSession $session): Response
    {
        Gate::authorize('view', $session);
        abort_unless($session->kind === SessionKind::Writing, 404);

        return $this->render($session);
    }

    public function weakPoint(Request $request, WeakPointBuilder $builder): Response|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $session = $builder->for($user);

        if ($session === null) {
            return redirect()->route('practice')->with('status', 'weak-point-empty');
        }

        return $this->render($session);
    }

    private function render(PracticeSession $session): Response
    {
        $items = $session->items()->get();
        $attempts = Attempt::query()->whereIn('practice_item_id', $items->pluck('id'))->get()->keyBy('practice_item_id');
        $lesson = $session->sourceLesson;

        return Inertia::render('practice/Writing', [
            'flash' => session('status'),
            'session' => [
                'ulid' => $session->ulid,
                'kind' => $session->kind->value,
                'lesson' => $lesson === null ? null : [
                    'ulid' => $lesson->ulid,
                    'title' => $lesson->title,
                    'lesson_date' => $lesson->lesson_date->toDateString(),
                ],
            ],
            'items' => $items->map(fn (PracticeItem $item) => self::present($item, $attempts->get($item->id)))->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(PracticeItem $item, ?Attempt $attempt): array
    {
        $content = $item->content;

        return [
            'ulid' => $item->ulid,
            'position' => $item->position,
            'type' => $item->type->value,
            'context' => $item->context?->value,
            'instruction' => $content['instruction'] ?? null,
            'sentence' => $content['sentence'] ?? '',
            'options' => $item->type === ItemType::Choice ? ($content['options'] ?? []) : null,
            'reported' => $item->reported,
            'attempt' => $attempt === null ? null : [
                'answer' => $attempt->answer,
                'verdict' => $attempt->verdict->value,
                'xp' => $attempt->xp,
                'correction' => $attempt->correction,
            ],
        ];
    }
}
