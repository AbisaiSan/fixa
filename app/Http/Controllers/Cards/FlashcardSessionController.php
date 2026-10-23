<?php

namespace App\Http\Controllers\Cards;

use App\Domain\Cards\DailyQueue;
use App\Domain\Cards\ReviewCard;
use App\Domain\Cards\Scheduling\ReviewScheduler;
use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Sessão de flashcards (J3-F1): a fila do dia, de todas as aulas ou de uma
 * só ("Praticar esta aula"). A sessão roda na tela e cada resposta vai
 * para o endpoint de respostas.
 */
class FlashcardSessionController extends Controller
{
    public function show(Request $request, DailyQueue $queue, ReviewScheduler $scheduler): Response
    {
        /** @var User $user */
        $user = $request->user();
        $timezone = $user->activeEnrollment->timezone ?? Enrollment::DEFAULT_TIMEZONE;
        $now = CarbonImmutable::now();

        // A aula, se vier, é procurada só entre as do aluno (ACE-04).
        $lesson = $request->filled('aula')
            ? Lesson::query()->where('ulid', $request->string('aula')->value())->firstOrFail()
            : null;

        $rush = $request->query('modo') === 'corrida';
        $cards = $queue->for($user, $lesson, dueOnly: $rush)->load(['sourceLesson:id,lesson_date', 'word:id,part_of_speech']);

        return Inertia::render('practice/Flashcards', [
            'mode' => $rush ? 'rush' : 'normal',
            'lesson' => $lesson === null ? null : ['ulid' => $lesson->ulid, 'title' => $lesson->title],
            'cards' => $cards->map(fn (Card $card) => self::present($card, $scheduler, $now, $timezone))->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(Card $card, ReviewScheduler $scheduler, CarbonImmutable $now, string $timezone): array
    {
        return [
            'ulid' => $card->ulid,
            'kind' => $card->kind->value,
            'front' => $card->front,
            'back' => $card->back,
            'example' => $card->example,
            'example_translation' => $card->example_translation,
            'part_of_speech' => $card->word?->part_of_speech,
            'lesson_date' => $card->sourceLesson?->lesson_date->toDateString(),
            'is_new' => $card->first_reviewed_at === null,
            'preview' => array_values($scheduler->preview(ReviewCard::stateOf($card), $now, $timezone)),
        ];
    }
}
