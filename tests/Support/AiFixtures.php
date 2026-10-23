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
     * @return array{cards: list<array<string, mixed>>}
     */
    public static function cards(int $count = 3): array
    {
        $samples = [
            ['kind' => 'word', 'front' => 'deadline', 'back' => 'prazo final', 'example' => "We've never missed a deadline.", 'example_translation' => 'Nunca perdemos um prazo.', 'word' => [
                'part_of_speech' => 'substantivo',
                'definition' => 'A data ou hora em que algo precisa estar pronto.',
                'examples' => [
                    ['target' => "We've never missed a deadline.", 'translation' => 'Nunca perdemos um prazo.'],
                    ['target' => 'The deadline is next Friday.', 'translation' => 'O prazo é na próxima sexta.'],
                ],
                'collocations' => ['meet a deadline', 'miss a deadline'],
            ]],
            ['kind' => 'word', 'front' => 'to apply for', 'back' => 'candidatar-se a', 'example' => 'I have applied for three jobs.', 'example_translation' => 'Eu me candidatei a três vagas.'],
            ['kind' => 'phrase', 'front' => 'Have you ever managed a team?', 'back' => 'Você já gerenciou uma equipe?', 'example' => 'Have you ever managed a team abroad?', 'example_translation' => 'Você já gerenciou uma equipe no exterior?'],
        ];

        return ['cards' => array_map(
            fn (int $index) => $index < 3 ? $samples[$index] : ['kind' => 'word', 'front' => "word {$index}", 'back' => "palavra {$index}", 'example' => "This is word {$index}.", 'example_translation' => "Esta é a palavra {$index}."],
            range(0, $count - 1),
        )];
    }

    /**
     * Sessão de escrita de exemplo da aula fictícia (J4-B1), como no protótipo.
     *
     * @return array{items: list<array<string, mixed>>}
     */
    public static function activities(): array
    {
        return ['items' => [
            [
                'type' => 'fill', 'context' => 'work',
                'instruction' => 'Use o Present Perfect do verbo entre parênteses.',
                'sentence' => 'I ___ (work) at this company since 2021.',
                'accepted' => ['have worked', "'ve worked", 'have been working', "'ve been working"],
                'corrected' => 'I have worked at this company since 2021.',
                'natural' => "I've been working at this company since 2021.",
                'why' => 'Com since, a ação começou no passado e continua agora: use have mais o particípio.',
                'point' => 'have + particípio',
            ],
            [
                'type' => 'translate', 'context' => 'work',
                'instruction' => 'Escreva em inglês.',
                'sentence' => 'Eu nunca trabalhei com vendas.',
                'accepted' => ['I have never worked in sales.', "I've never worked in sales."],
                'corrected' => 'I have never worked in sales.',
                'natural' => "I've never worked in sales before.",
                'why' => 'Para experiências sem dizer quando, use o Present Perfect. Áreas de atuação levam in.',
                'point' => 'never + Present Perfect',
            ],
            [
                'type' => 'context', 'context' => 'work',
                'instruction' => 'Conte uma conquista sua.',
                'sentence' => 'Numa entrevista, conte uma conquista usando "achievement" e o Present Perfect.',
                'accepted' => [],
                'corrected' => 'My biggest achievement has been leading our team through a migration.',
                'natural' => "One achievement I'm proud of is that I've led our team through a migration.",
                'why' => 'Conquista com efeito hoje pede o Present Perfect.',
                'point' => 'achievement',
            ],
            [
                'type' => 'fill', 'context' => 'daily',
                'instruction' => 'Complete com a palavra que falta.',
                'sentence' => 'Have you ___ been to Chile?',
                'accepted' => ['ever'],
                'corrected' => 'Have you ever been to Chile?',
                'natural' => 'Have you ever been to Chile?',
                'why' => 'Ever entra em perguntas sobre experiência: Have you ever quer dizer Você já.',
                'point' => 'ever',
            ],
            [
                'type' => 'context', 'context' => 'travel',
                'instruction' => 'Conte como a viagem está indo.',
                'sentence' => 'Você está numa viagem. Conte como ela está indo usando "so far".',
                'accepted' => [],
                'corrected' => "So far, I've visited three museums.",
                'natural' => "So far, I've visited three museums and tried the local food.",
                'why' => 'So far pede o Present Perfect, porque o período ainda não terminou.',
                'point' => 'so far',
            ],
        ]];
    }
}
