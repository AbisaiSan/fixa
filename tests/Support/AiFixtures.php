<?php

namespace Tests\Support;

/**
 * Respostas de exemplo da IA, com os dados fictícios do protótipo
 * (aula de Present Perfect). Nunca material real de aula.
 */
final class AiFixtures
{
    /**
     * @return array<string, mixed>
     */
    public static function lessonReview(): array
    {
        $example = fn (string $target, string $translation) => compact('target', 'translation');

        return [
            'summary' => 'A aula tratou do Present Perfect para falar de experiências e de ações que começaram no passado e continuam até agora, com for e since.',
            'concepts' => [
                [
                    'name' => 'Present Perfect',
                    'explanation' => 'Liga o passado ao presente. Use have ou has mais o particípio.',
                    'examples' => [
                        $example('I have worked here since 2022.', 'Trabalho aqui desde 2022.'),
                        $example('She has never missed a deadline.', 'Ela nunca perdeu um prazo.'),
                        $example('Have you ever managed a team?', 'Você já gerenciou uma equipe?'),
                    ],
                ],
            ],
            'common_mistakes' => [
                ['wrong' => 'I work here since 2022.', 'right' => 'I have worked here since 2022.', 'why' => 'Com since, a ação continua até agora.'],
            ],
            'can_do' => [
                'Consigo contar há quanto tempo faço algo.',
                'Consigo perguntar sobre experiências.',
                'Consigo usar for e since.',
            ],
        ];
    }

    /**
     * Cartões de exemplo da mesma aula fictícia (J3-B1).
     *
     * @return array{cards: list<array<string, string>>}
     */
    public static function cards(int $count = 3): array
    {
        $samples = [
            ['kind' => 'word', 'front' => 'deadline', 'back' => 'prazo final', 'example' => "We've never missed a deadline.", 'example_translation' => 'Nunca perdemos um prazo.'],
            ['kind' => 'word', 'front' => 'to apply for', 'back' => 'candidatar-se a', 'example' => 'I have applied for three jobs.', 'example_translation' => 'Eu me candidatei a três vagas.'],
            ['kind' => 'phrase', 'front' => 'Have you ever managed a team?', 'back' => 'Você já gerenciou uma equipe?', 'example' => 'Have you ever managed a team abroad?', 'example_translation' => 'Você já gerenciou uma equipe no exterior?'],
        ];

        return ['cards' => array_map(
            fn (int $index) => $index < 3 ? $samples[$index] : ['kind' => 'word', 'front' => "word {$index}", 'back' => "palavra {$index}", 'example' => "This is word {$index}.", 'example_translation' => "Esta é a palavra {$index}."],
            range(0, $count - 1),
        )];
    }
}
