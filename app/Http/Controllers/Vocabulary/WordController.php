<?php

namespace App\Http\Controllers\Vocabulary;

use App\Domain\Ai\DailyAiAllowance;
use App\Domain\Lessons\Reports\ReportItem;
use App\Domain\Lessons\Reports\ReportReason;
use App\Domain\Vocabulary\AddOwnWord;
use App\Domain\Vocabulary\EditWord;
use App\Domain\Vocabulary\WordSearch;
use App\Domain\Vocabulary\WordState;
use App\Http\Controllers\Controller;
use App\Models\ItemReport;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Word;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vocabulário do aluno (J3-B8, J3-F5, VOC-01).
 */
class WordController extends Controller
{
    public const LIST_LIMIT = 300;

    public function index(Request $request, WordSearch $search, DailyAiAllowance $allowance): Response
    {
        return $this->page($request, $search, $allowance, null);
    }

    public function show(Request $request, Word $word, WordSearch $search, DailyAiAllowance $allowance): Response
    {
        Gate::authorize('view', $word);

        return $this->page($request, $search, $allowance, $word);
    }

    public function update(Request $request, Word $word, EditWord $edit): RedirectResponse
    {
        Gate::authorize('update', $word);

        $validated = $request->validate([
            'translation' => ['required', 'string', 'max:200'],
            'part_of_speech' => ['nullable', 'string', 'max:30'],
            'definition' => ['nullable', 'string', 'max:300'],
        ], attributes: ['translation' => 'tradução', 'part_of_speech' => 'classe', 'definition' => 'definição']);

        /** @var User $user */
        $user = $request->user();
        $edit->handle($user, $word, $validated);

        return back()->with('status', 'word-updated');
    }

    public function report(Request $request, Word $word, ReportItem $reports): RedirectResponse
    {
        Gate::authorize('update', $word);

        $validated = $request->validate([
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'details' => ['nullable', 'string', 'max:500'],
        ]);

        $reports->handle($word, Word::ITEM, ReportReason::from($validated['reason']), $validated['details'] ?? null);

        return back()->with('status', 'item-reported');
    }

    public function withdrawReport(Word $word, ItemReport $report, ReportItem $reports): RedirectResponse
    {
        Gate::authorize('update', $word);
        Gate::authorize('update', $report);

        abort_unless($report->reportable_type === $word->getMorphClass() && $report->reportable_id === $word->id, 404);

        $reports->withdraw($report);

        return back()->with('status', 'report-withdrawn');
    }

    private function page(Request $request, WordSearch $search, DailyAiAllowance $allowance, ?Word $selected): Response
    {
        /** @var User $user */
        $user = $request->user();
        $filters = [
            'q' => $request->string('q')->limit(80, '')->value(),
            'state' => $request->string('estado')->value(),
            'lesson' => $request->string('aula')->value(),
            'origin' => $request->string('origem')->value(),
        ];

        $withoutState = $search->query($user, [...$filters, 'state' => null])->toBase()->getCountForPagination();
        $counts = ['all' => $withoutState];
        foreach (WordState::cases() as $state) {
            $counts[$state->value] = $search->query($user, [...$filters, 'state' => $state->value])->toBase()->getCountForPagination();
        }

        $words = $search->query($user, $filters)->limit(self::LIST_LIMIT)->get();

        return Inertia::render('app/Vocabulary', [
            'status' => Word::query()->exists() ? 'ready' : 'empty',
            'filters' => ['q' => $filters['q'], 'estado' => $filters['state'], 'aula' => $filters['lesson'], 'origem' => $filters['origin']],
            'counts' => $counts,
            'words' => $words->map(fn (Word $word) => [
                'ulid' => $word->ulid,
                'term' => $word->term,
                'part_of_speech' => $word->part_of_speech,
                'translation' => $word->translation,
                'state' => $word->state->value,
            ])->all(),
            'lessons' => Lesson::query()->orderByDesc('lesson_date')->get(['ulid', 'title', 'lesson_date'])
                ->map(fn (Lesson $lesson) => ['ulid' => $lesson->ulid, 'title' => $lesson->title, 'lesson_date' => $lesson->lesson_date->toDateString()])
                ->all(),
            'selected' => $selected === null ? null : $this->detail($selected),
            'aiLeft' => $allowance->left($user),
            'flash' => session('status'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Word $word): array
    {
        $report = $word->reports()->open()->first(['ulid']);

        return [
            'ulid' => $word->ulid,
            'term' => $word->term,
            'part_of_speech' => $word->part_of_speech,
            'translation' => $word->translation,
            'definition' => $word->definition,
            'examples' => $word->examples ?? [],
            'collocations' => $word->collocations ?? [],
            'state' => $word->state->value,
            'origin' => $word->origin->value,
            'lesson' => $word->sourceLesson === null ? null : [
                'ulid' => $word->sourceLesson->ulid,
                'lesson_date' => $word->sourceLesson->lesson_date->toDateString(),
            ],
            'generated' => $word->provider !== null,
            'report' => $report?->ulid,
        ];
    }

    public function store(Request $request, AddOwnWord $addWord): RedirectResponse
    {
        $validated = $request->validate([
            'term' => ['required', 'string', 'max:120'],
            'translation' => ['nullable', 'string', 'max:200'],
            'part_of_speech' => ['nullable', 'string', 'max:30'],
            'definition' => ['nullable', 'string', 'max:300'],
            'fill_with_ai' => ['boolean'],
        ], attributes: ['term' => 'palavra', 'translation' => 'tradução', 'part_of_speech' => 'classe', 'definition' => 'definição']);

        /** @var User $user */
        $user = $request->user();
        $addWord->handle($user, $validated, $request->boolean('fill_with_ai'));

        return back()->with('status', 'word-added');
    }
}
