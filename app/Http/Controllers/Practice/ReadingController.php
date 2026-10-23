<?php

namespace App\Http\Controllers\Practice;

use App\Domain\Cards\CardIdentity;
use App\Domain\Practice\SessionKind;
use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use App\Models\User;
use App\Models\Word;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Leitura (J4-F5, ATV-04): o texto com as palavras do glossário marcadas,
 * quais o aluno já tem no vocabulário e as perguntas.
 */
class ReadingController extends Controller
{
    public function show(Request $request, PracticeSession $session): Response
    {
        Gate::authorize('view', $session);
        abort_unless($session->kind === SessionKind::Reading, 404);

        /** @var User $user */
        $user = $request->user();
        $content = $session->content ?? [];
        /** @var list<array{form: string, term: string, part_of_speech: string, translation: string}> $glossary */
        $glossary = $content['glossary'] ?? [];
        $known = Word::forUser($user)
            ->whereIn('dedupe_key', array_map(fn (array $entry) => CardIdentity::key($entry['term'], $entry['translation']), $glossary))
            ->pluck('dedupe_key')
            ->all();

        $items = $session->items()->get();
        $attempts = Attempt::query()->whereIn('practice_item_id', $items->pluck('id'))->get()->keyBy('practice_item_id');
        $lesson = $session->sourceLesson;

        return Inertia::render('app/Reading', [
            'session' => [
                'ulid' => $session->ulid,
                'title' => $content['title'] ?? '',
                'paragraphs' => $content['paragraphs'] ?? [],
                'lesson' => $lesson === null ? null : ['ulid' => $lesson->ulid, 'lesson_date' => $lesson->lesson_date->toDateString()],
            ],
            'glossary' => array_map(fn (array $entry) => [
                ...$entry,
                'known' => in_array(CardIdentity::key($entry['term'], $entry['translation']), $known, true),
            ], $glossary),
            'questions' => $items->map(fn (PracticeItem $item) => PracticeSessionController::present($item, $attempts->get($item->id)))->all(),
        ]);
    }
}
