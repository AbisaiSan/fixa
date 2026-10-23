<?php

use App\Domain\Ai\AiTask;
use App\Domain\Ai\Exceptions\AiProviderException;
use App\Domain\Ai\Providers\FakeAiProvider;
use App\Domain\Vocabulary\WordOrigin;
use App\Models\Card;
use App\Models\User;
use App\Models\Word;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->ai = app(FakeAiProvider::class);
    $this->details = [
        'translation' => 'ponto fraco',
        'part_of_speech' => 'substantivo',
        'definition' => 'Algo em que você ainda não é tão bom.',
        'examples' => [
            ['target' => 'My main weakness is public speaking.', 'translation' => 'Meu principal ponto fraco é falar em público.'],
            ['target' => 'Everyone has weaknesses.', 'translation' => 'Todo mundo tem pontos fracos.'],
        ],
        'collocations' => ['main weakness', 'overcome a weakness'],
    ];
    $this->actingAs($this->user);
});

test('a word the student adds is "mine" and gets a card (VOC-03)', function () {
    $this->post(route('words.store'), ['term' => 'weakness', 'translation' => 'fraqueza'])
        ->assertSessionHas('status', 'word-added');

    $word = Word::sole();
    $card = Card::sole();

    expect($word->origin)->toBe(WordOrigin::Mine)
        ->and($word->source_lesson_id)->toBeNull()
        ->and($word->provider)->toBeNull()
        ->and($word->language)->toBe('en')
        ->and($card->word_id)->toBe($word->id)
        ->and($card->front)->toBe('weakness')
        ->and($card->source_lesson_id)->toBeNull();
    $this->ai->assertSentCount(0);
});

test('the AI can fill the word, and what the student wrote wins', function () {
    $this->ai->respondWith(AiTask::WordDetails, $this->details);

    $this->post(route('words.store'), ['term' => 'weakness', 'definition' => 'Minha definição.', 'fill_with_ai' => true]);

    $word = Word::sole();
    $request = $this->ai->sent(AiTask::WordDetails)[0];

    expect($word->translation)->toBe('ponto fraco')
        ->and($word->definition)->toBe('Minha definição.')
        ->and($word->examples)->toHaveCount(2)
        ->and($word->provider)->toBe('fake')
        ->and($word->prompt_version)->toBe('v1')
        ->and(Card::sole()->example)->toBe('My main weakness is public speaking.')
        ->and($request->material)->toContain('weakness')
        ->and($request->instructions)->not->toContain('weakness');
});

test('the AI fill stops at the daily limit, and the student can still save (ATV-05)', function () {
    config(['fixa.ai.daily_student_limit' => 1]);
    DB::table('ai_calls')->insert(['user_id' => $this->user->id, 'provider' => 'fake', 'model' => 'fake-model', 'task' => 'word_details', 'prompt_version' => 'v1', 'outcome' => 'ok', 'created_at' => now()]);

    $this->post(route('words.store'), ['term' => 'weakness', 'fill_with_ai' => true])
        ->assertSessionHasErrors(['translation' => 'Você chegou ao limite de pedidos à IA de hoje. Escreva a tradução para salvar.']);

    $this->post(route('words.store'), ['term' => 'weakness', 'translation' => 'fraqueza', 'fill_with_ai' => true])
        ->assertSessionHas('status', 'word-added');

    $this->ai->assertSentCount(0);
});

test('lesson generation does not count towards the student daily limit', function () {
    config(['fixa.ai.daily_student_limit' => 1]);
    DB::table('ai_calls')->insert(['user_id' => $this->user->id, 'provider' => 'fake', 'model' => 'fake-model', 'task' => 'cards', 'prompt_version' => 'v2', 'outcome' => 'ok', 'created_at' => now()]);
    $this->ai->respondWith(AiTask::WordDetails, $this->details);

    $this->post(route('words.store'), ['term' => 'weakness', 'fill_with_ai' => true])->assertSessionHas('status', 'word-added');
});

test('when the AI fails the student is asked for the translation', function () {
    $this->ai->respondWith(AiTask::WordDetails, new AiProviderException('down', retryable: false));

    $this->post(route('words.store'), ['term' => 'weakness', 'fill_with_ai' => true])
        ->assertSessionHasErrors(['translation' => 'Não conseguimos completar com IA agora. Escreva a tradução para salvar.']);

    expect(Word::count())->toBe(0);
});

test('a word already in the vocabulary is not added twice', function () {
    $this->post(route('words.store'), ['term' => 'weakness', 'translation' => 'fraqueza']);

    $this->post(route('words.store'), ['term' => 'Weakness', 'translation' => 'fraqueza'])
        ->assertSessionHasErrors(['term' => 'Esta palavra já está no seu vocabulário.']);
});
