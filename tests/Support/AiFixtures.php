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
}
