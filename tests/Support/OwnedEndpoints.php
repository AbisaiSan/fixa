<?php

namespace Tests\Support;

use App\Domain\Lessons\LessonStatus;
use App\Domain\Lessons\Reports\ReportItem;
use App\Domain\Lessons\Reports\ReportReason;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\LessonFile;
use App\Models\LessonReview;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Fixtures\OwnedNote;
use Tests\Fixtures\OwnedNotePolicy;

/**
 * Matriz de acesso entre alunos (J1-B9, ACE-04).
 *
 * Todo endpoint que recebe um dado de aluno entra em all(): o teste
 * CrossStudentAccessTest tenta ler, alterar e apagar o dado do aluno A
 * logado como o aluno B, e exige que nada aconteça. Uma rota nova com
 * modelo que usa BelongsToUser e fica fora daqui faz o teste falhar.
 */
final class OwnedEndpoints
{
    /**
     * @return array<string, array{method: string, route: string, make: Closure(User): array{resource: Model, params: array<int, string>, numeric: array<int, int>}, payload: array<string, mixed>, signed: bool}>
     */
    public static function all(): array
    {
        return [
            // Exemplo com o modelo de teste, que garante a matriz funcionando.
            'fixture: ler nota' => self::endpoint('get', 'test.notes.show', self::makeNote(...)),
            'fixture: alterar nota' => self::endpoint('patch', 'test.notes.update', self::makeNote(...), ['body' => 'invadido']),
            'fixture: apagar nota' => self::endpoint('delete', 'test.notes.destroy', self::makeNote(...)),

            // J2: aulas e arquivos de aula.
            'ver aula' => self::endpoint('get', 'lessons.show', self::makeLesson(...)),
            'baixar arquivo da aula' => self::endpoint('get', 'lessons.files.show', self::makeLessonFile(...), signed: true),
            'editar aula' => self::endpoint('patch', 'lessons.update', self::makeLesson(...), ['title' => 'invadido', 'lesson_date' => '2026-10-20']),
            'apagar aula' => self::endpoint('delete', 'lessons.destroy', self::makeLesson(...)),
            'regenerar revisão' => self::endpoint('post', 'lessons.regenerate', self::makeReadyLesson(...)),
            'tentar de novo' => self::endpoint('post', 'lessons.retry', self::makeFailedLesson(...)),
            'editar resumo' => self::endpoint('patch', 'lessons.review.update', self::makeReview(...), ['summary' => 'invadido']),
            'marcar você consegue' => self::endpoint('put', 'lessons.review.can-do', self::makeReview(...), ['done' => [0, 1]]),
            'editar conceito' => self::endpoint('patch', 'lessons.review.concepts.update', self::makeConcept(...), ['explanation' => 'invadido']),
            'remover conceito' => self::endpoint('delete', 'lessons.review.concepts.destroy', self::makeConcept(...)),
            'reportar item' => self::endpoint('post', 'lessons.reports.store', self::makeReview(...), ['item' => 'summary', 'reason' => 'other']),
            // J3: flashcards.
            'responder cartão' => self::endpoint('post', 'cards.reviews.store', self::makeCard(...), ['rating' => 3]),
            'editar cartão' => self::endpoint('patch', 'cards.update', self::makeCard(...), ['front' => 'invadido', 'back' => 'invadido']),
            'apagar cartão' => self::endpoint('delete', 'cards.destroy', self::makeCard(...)),
            'suspender cartão' => self::endpoint('post', 'cards.suspend', self::makeCard(...)),
            'reativar cartão' => self::endpoint('delete', 'cards.resume', self::makeCard(...)),
            'reportar cartão' => self::endpoint('post', 'cards.reports.store', self::makeCard(...), ['reason' => 'other']),
            'desfazer report do cartão' => self::endpoint('delete', 'cards.reports.destroy', self::makeCardReport(...)),
            'desfazer report' => self::endpoint('delete', 'lessons.reports.destroy', self::makeReport(...)),
        ];
    }

    /**
     * Nomes de rota cobertos pela matriz.
     *
     * @return list<string>
     */
    public static function routeNames(): array
    {
        return array_values(array_unique(array_column(self::all(), 'route')));
    }

    /**
     * Prepara o que os exemplos da matriz precisam: tabela, Policy e rotas de teste.
     */
    public static function bootFixtures(): void
    {
        Storage::fake(LessonFile::DISK);

        Schema::create('owned_notes', function (Blueprint $table) {
            $table->id();
            $table->ulid()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Gate::policy(OwnedNote::class, OwnedNotePolicy::class);

        Route::middleware(['web', 'auth'])->prefix('_test/notes')->name('test.notes.')->group(function () {
            Route::get('{note}', function (OwnedNote $note) {
                Gate::authorize('view', $note);

                return $note->body;
            })->name('show');

            Route::patch('{note}', function (OwnedNote $note) {
                Gate::authorize('update', $note);
                $note->update(['body' => request('body')]);

                return 'ok';
            })->name('update');

            Route::delete('{note}', function (OwnedNote $note) {
                Gate::authorize('delete', $note);
                $note->delete();

                return 'ok';
            })->name('destroy');
        });

        app('router')->getRoutes()->refreshNameLookups();
    }

    /**
     * @param  Closure(User): array{resource: Model, params: array<int, string>, numeric: array<int, int>}  $make
     * @param  array<string, mixed>  $payload
     * @return array{method: string, route: string, make: Closure(User): array{resource: Model, params: array<int, string>, numeric: array<int, int>}, payload: array<string, mixed>, signed: bool}
     */
    private static function endpoint(string $method, string $route, Closure $make, array $payload = [], bool $signed = false): array
    {
        return compact('method', 'route', 'make', 'payload', 'signed');
    }

    /**
     * @return array{resource: Model, params: array<int, string>, numeric: array<int, int>}
     */
    private static function makeNote(User $owner): array
    {
        $note = (new OwnedNote(['body' => 'nota da aluna A']))->forceFill(['user_id' => $owner->id]);
        $note->save();

        return ['resource' => $note, 'params' => [$note->ulid], 'numeric' => [$note->id]];
    }

    /**
     * @return array{resource: Model, params: array<int, string>, numeric: array<int, int>}
     */
    private static function makeLesson(User $owner): array
    {
        $lesson = Lesson::factory()->forUser($owner)->create(['title' => 'nota da aluna A']);

        return ['resource' => $lesson, 'params' => [$lesson->ulid], 'numeric' => [$lesson->id]];
    }

    /**
     * @return array{resource: Model, params: array<int, string>, numeric: array<int, int>}
     */
    private static function makeCard(User $owner): array
    {
        $lesson = Lesson::factory()->forUser($owner)->status(LessonStatus::Ready)->create();
        $card = Card::factory()->forLesson($lesson)->create(['back' => 'nota da aluna A']);

        return ['resource' => $card, 'params' => [$card->ulid], 'numeric' => [$card->id]];
    }

    /**
     * @return array{resource: Model, params: array<int, string>, numeric: array<int, int>}
     */
    private static function makeCardReport(User $owner): array
    {
        ['resource' => $card] = self::makeCard($owner);
        /** @var Card $card */
        $report = app(ReportItem::class)->handle($card, Card::ITEM, ReportReason::Other, 'nota da aluna A');

        return ['resource' => $report, 'params' => [$card->ulid, $report->ulid], 'numeric' => [$card->id, $report->id]];
    }

    /**
     * @return array{resource: Model, params: array<int, string>, numeric: array<int, int>}
     */
    private static function makeReadyLesson(User $owner): array
    {
        Queue::fake();
        [$lesson] = self::reviewFor($owner);

        return ['resource' => $lesson, 'params' => [$lesson->ulid], 'numeric' => [$lesson->id]];
    }

    /**
     * @return array{resource: Model, params: array<int, string>, numeric: array<int, int>}
     */
    private static function makeFailedLesson(User $owner): array
    {
        Queue::fake();
        $lesson = Lesson::factory()->forUser($owner)->status(LessonStatus::Failed)->create(['failure_reason' => 'ai_unavailable']);

        return ['resource' => $lesson, 'params' => [$lesson->ulid], 'numeric' => [$lesson->id]];
    }

    /**
     * A revisão é o dado conferido: o pedido do outro aluno não pode mudá-la.
     *
     * @return array{resource: Model, params: array<int, string>, numeric: array<int, int>}
     */
    private static function makeReview(User $owner): array
    {
        [$lesson, $review] = self::reviewFor($owner);

        return ['resource' => $review, 'params' => [$lesson->ulid], 'numeric' => [$lesson->id]];
    }

    /**
     * @return array{resource: Model, params: array<int, string>, numeric: array<int, int>}
     */
    private static function makeConcept(User $owner): array
    {
        [$lesson, $review] = self::reviewFor($owner);
        $key = $review->concepts[0]['key'];

        return ['resource' => $review, 'params' => [$lesson->ulid, $key], 'numeric' => [$lesson->id, $key]];
    }

    /**
     * @return array{resource: Model, params: array<int, string>, numeric: array<int, int>}
     */
    private static function makeReport(User $owner): array
    {
        [$lesson, $review] = self::reviewFor($owner);
        $report = app(ReportItem::class)->handle($review, LessonReview::SUMMARY, ReportReason::Other, 'nota da aluna A');

        return ['resource' => $report, 'params' => [$lesson->ulid, $report->ulid], 'numeric' => [$lesson->id, $report->id]];
    }

    /**
     * @return array{0: Lesson, 1: LessonReview}
     */
    private static function reviewFor(User $owner): array
    {
        $lesson = Lesson::factory()->forUser($owner)->status(LessonStatus::Ready)->create();

        return [$lesson, LessonReview::factory()->for($lesson)->create()];
    }

    /**
     * @return array{resource: Model, params: array<int, string>, numeric: array<int, int>}
     */
    private static function makeLessonFile(User $owner): array
    {
        $lesson = Lesson::factory()->forUser($owner)->create();
        Storage::disk(LessonFile::DISK)->put("{$owner->ulid}/{$lesson->ulid}/a.pdf", '%PDF nota da aluna A');

        $file = (new LessonFile)->forceFill([
            'user_id' => $owner->id,
            'lesson_id' => $lesson->id,
            'path' => "{$owner->ulid}/{$lesson->ulid}/a.pdf",
            'mime_type' => 'application/pdf',
            'size_bytes' => 20,
            'sha256' => str_repeat('a', 64),
        ]);
        $file->save();

        return ['resource' => $file, 'params' => [$lesson->ulid, $file->ulid], 'numeric' => [$lesson->id, $file->id]];
    }
}
