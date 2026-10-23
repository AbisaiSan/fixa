<?php

namespace Database\Factories;

use App\Domain\Lessons\LessonStatus;
use App\Models\Lesson;
use App\Models\LessonReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Revisão fictícia da aula de Present Perfect do protótipo. Nunca material real.
 *
 * @extends Factory<LessonReview>
 */
class LessonReviewFactory extends Factory
{
    public function definition(): array
    {
        $example = fn (string $target, string $translation) => compact('target', 'translation');

        return [
            'lesson_id' => Lesson::factory()->status(LessonStatus::Ready),
            'summary' => 'A aula tratou do Present Perfect para falar de experiências e de ações que continuam até agora.',
            'concepts' => LessonReview::withConceptKeys([
                [
                    'name' => 'Present Perfect',
                    'explanation' => 'Liga o passado ao presente. Use have ou has mais o particípio.',
                    'examples' => [
                        $example('I have worked here since 2022.', 'Trabalho aqui desde 2022.'),
                        $example('She has never missed a deadline.', 'Ela nunca perdeu um prazo.'),
                        $example('Have you ever managed a team?', 'Você já gerenciou uma equipe?'),
                    ],
                ],
                [
                    'name' => 'For e since',
                    'explanation' => 'For indica a duração; since, o ponto de partida.',
                    'examples' => [
                        $example('I have lived here for ten years.', 'Moro aqui há dez anos.'),
                        $example('He has been here since Monday.', 'Ele está aqui desde segunda.'),
                        $example('We have known each other for a long time.', 'Nós nos conhecemos há muito tempo.'),
                    ],
                ],
            ]),
            'common_mistakes' => [
                ['wrong' => 'I work here since 2022.', 'right' => 'I have worked here since 2022.', 'why' => 'Com since, a ação continua até agora.'],
            ],
            'can_do' => [
                'Consigo contar há quanto tempo faço algo.',
                'Consigo perguntar sobre experiências.',
                'Consigo usar for e since.',
            ],
            'provider' => 'fake',
            'model' => 'fake',
            'prompt_version' => 'v1',
        ];
    }

    /**
     * A revisão é do mesmo aluno da aula.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (LessonReview $review): void {
            $review->user_id ??= Lesson::withoutGlobalScopes()->findOrFail($review->lesson_id)->user_id;
        });
    }
}
