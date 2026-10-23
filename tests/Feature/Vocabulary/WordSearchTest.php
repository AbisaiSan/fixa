<?php

use App\Domain\Vocabulary\WordOrigin;
use App\Domain\Vocabulary\WordSearch;
use App\Domain\Vocabulary\WordState;
use App\Models\Lesson;
use App\Models\User;
use App\Models\Word;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->lesson = Lesson::factory()->forUser($this->user)->create();
    $this->other = Lesson::factory()->forUser($this->user)->create();
    $this->word = fn (string $term, string $translation, array $extra = []) => Word::factory()
        ->forLesson($this->lesson)->create(['term' => $term, 'translation' => $translation, ...$extra]);
    $this->search = fn (array $filters) => app(WordSearch::class)->query($this->user, $filters)->pluck('term')->all();
});

test('search ignores accents and case, in the term and the translation', function () {
    ($this->word)('résumé', 'currículo');
    ($this->word)('deadline', 'prazo final');

    expect(($this->search)(['q' => 'RESUME']))->toBe(['résumé'])
        ->and(($this->search)(['q' => 'curriculo']))->toBe(['résumé'])
        ->and(($this->search)(['q' => 'Prazo']))->toBe(['deadline']);
});

test('search tolerates a small typo', function () {
    ($this->word)('achievement', 'conquista');
    ($this->word)('deadline', 'prazo final');

    expect(($this->search)(['q' => 'achievment']))->toBe(['achievement']);
});

test('search treats percent and underscore as text', function () {
    ($this->word)('deadline', 'prazo final');

    expect(($this->search)(['q' => '%']))->toBe([])
        ->and(($this->search)(['q' => '_']))->toBe([]);
});

test('filters by state, lesson and origin', function () {
    ($this->word)('deadline', 'prazo final', ['state' => WordState::Mastered]);
    ($this->word)('strength', 'ponto forte', ['state' => WordState::Learning]);
    $shared = ($this->word)('weakness', 'ponto fraco');
    DB::table('lesson_word')->insert(['word_id' => $shared->id, 'lesson_id' => $this->other->id]);
    Word::factory()->create(['user_id' => $this->user->id, 'origin' => WordOrigin::Mine, 'term' => 'mine', 'translation' => 'minha']);

    expect(($this->search)(['state' => 'mastered']))->toBe(['deadline'])
        ->and(($this->search)(['lesson' => $this->other->ulid]))->toBe(['weakness'])
        ->and(($this->search)(['origin' => 'mine']))->toBe(['mine']);
});

test('the list is alphabetical without accents and never shows another student', function () {
    ($this->word)('zeal', 'zelo');
    ($this->word)('ébauche', 'esboço');
    ($this->word)('apply', 'candidatar-se');
    Word::factory()->create(['term' => 'alheia']);

    expect(($this->search)([]))->toBe(['apply', 'ébauche', 'zeal']);
});
