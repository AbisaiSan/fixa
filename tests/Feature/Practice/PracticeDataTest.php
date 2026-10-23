<?php

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Lessons\DeleteLesson;
use App\Jobs\ProcessLesson;
use App\Models\Attempt;
use App\Models\Lesson;
use App\Models\Mistake;
use App\Models\PracticeItem;
use App\Models\PracticeSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\AiFixtures;

test('practice tables keep answer, result and correction (J4-B11)', function () {
    expect(Schema::getColumnListing('attempts'))->toContain('answer', 'verdict', 'correction', 'xp', 'source', 'answered_at')
        ->and(Schema::getColumnListing('practice_items'))->toContain('practice_session_id', 'type', 'context', 'content', 'reported')
        ->and(Schema::getColumnListing('practice_sessions'))->toContain('source_lesson_id', 'kind', 'status', 'prompt_version');
});

test('removing a lesson removes its practice and answers, and keeps the error notebook', function () {
    app(FakeAiProvider::class)
        ->respondWith(AiTask::LessonReview, AiFixtures::lessonReview())
        ->respondWith(AiTask::Cards, AiFixtures::cards())
        ->respondWith(AiTask::Activities, AiFixtures::activities())
        ->respondWith(AiTask::Reading, AiFixtures::reading());
    $lesson = Lesson::factory()->create();
    $lesson->forceFill(['extracted_text' => str_repeat('I have worked here since 2022. ', 12)])->save();
    app()->call([new ProcessLesson($lesson->id), 'handle']);

    $user = $lesson->user;
    $this->actingAs($user);
    $item = PracticeItem::query()->orderBy('id')->first();
    $this->postJson(route('practice.attempts.store', $item), ['answer' => 'worked'])->assertOk();

    expect(Attempt::count())->toBe(1)->and(Mistake::count())->toBe(1);

    app(DeleteLesson::class)->handle($user, $lesson);

    expect(PracticeSession::withoutGlobalScopes()->count())->toBe(0)
        ->and(PracticeItem::withoutGlobalScopes()->count())->toBe(0)
        ->and(Attempt::withoutGlobalScopes()->count())->toBe(0)
        ->and(Mistake::withoutGlobalScopes()->sole()->source_lesson_id)->toBeNull();
});

test('no practice table stores student text in clear', function () {
    app(FakeAiProvider::class);
    $lesson = Lesson::factory()->create();
    $session = (new PracticeSession)->forceFill(['user_id' => $lesson->user_id, 'source_lesson_id' => $lesson->id, 'kind' => 'writing']);
    $session->save();
    $item = (new PracticeItem)->forceFill(['user_id' => $lesson->user_id, 'practice_session_id' => $session->id, 'position' => 1, 'type' => 'fill',
        'content' => AiFixtures::activities()['items'][0]]);
    $item->save();
    $this->actingAs($lesson->user)->postJson(route('practice.attempts.store', $item), ['answer' => 'segredo do aluno']);

    foreach (['practice_items', 'attempts', 'mistakes'] as $table) {
        expect(json_encode(DB::table($table)->get()))->not->toContain('segredo do aluno')->not->toContain('since 2021');
    }
});
