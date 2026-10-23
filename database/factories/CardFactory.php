<?php

namespace Database\Factories;

use App\Domain\Cards\CardKind;
use App\Models\Card;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Cartões fictícios da aula de Present Perfect do protótipo. Nunca material real.
 *
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'kind' => CardKind::Word,
            'front' => 'deadline',
            'back' => 'prazo final',
            'example' => "We've never missed a deadline.",
            'example_translation' => 'Nunca perdemos um prazo.',
            'provider' => 'fake',
            'model' => 'fake-model',
            'prompt_version' => 'v1',
        ];
    }

    public function forLesson(Lesson $lesson): static
    {
        return $this->state(['user_id' => $lesson->user_id, 'source_lesson_id' => $lesson->id]);
    }
}
