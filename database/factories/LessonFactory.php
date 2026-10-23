<?php

namespace Database\Factories;

use App\Domain\Lessons\LessonSource;
use App\Domain\Lessons\LessonStatus;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Aulas fictícias, como a de Present Perfect do protótipo. Nunca material real.
 *
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => 'Present Perfect no trabalho',
            'lesson_date' => '2026-10-21',
            'source' => LessonSource::Text,
            'status' => LessonStatus::Queued,
            'rights_declared_at' => now(),
        ];
    }

    /**
     * A aula fica na matrícula ativa do aluno, no idioma dela.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Lesson $lesson): void {
            $enrollment = User::query()->findOrFail($lesson->user_id)->activeEnrollment;

            $lesson->enrollment_id ??= $enrollment?->id;
            $lesson->language ??= $enrollment->target_language ?? 'en';
        });
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    public function status(LessonStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
