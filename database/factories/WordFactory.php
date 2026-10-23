<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\User;
use App\Models\Word;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Palavras fictícias da aula de Present Perfect do protótipo.
 *
 * @extends Factory<Word>
 */
class WordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'language' => 'en',
            'term' => 'deadline',
            'part_of_speech' => 'substantivo',
            'translation' => 'prazo final',
            'definition' => 'A data ou hora em que algo precisa estar pronto.',
            'examples' => [
                ['target' => "We've never missed a deadline.", 'translation' => 'Nunca perdemos um prazo.'],
                ['target' => 'The deadline is next Friday.', 'translation' => 'O prazo é na próxima sexta.'],
            ],
            'collocations' => ['meet a deadline', 'miss a deadline', 'tight deadline'],
        ];
    }

    public function forLesson(Lesson $lesson): static
    {
        return $this->state(['user_id' => $lesson->user_id, 'source_lesson_id' => $lesson->id, 'language' => $lesson->language]);
    }
}
