<?php

/*
| Termos de uso. RASCUNHO: revisar com advogado antes de qualquer usuário
| externo (plano, "Do local à venda"). Mudou o texto, suba a versão em
| config/fixa.php para pedir novo aceite.
*/

return [
    'title' => 'Termos de uso',
    'draft' => true,
    'sections' => [
        [
            'title' => 'O que é o Fixa',
            'paragraphs' => [
                'O Fixa é uma plataforma de estudo de idiomas que transforma o material das suas aulas em revisão, flashcards e atividades, com ajuda de inteligência artificial.',
                'O Fixa complementa as aulas do seu professor e não substitui o acompanhamento dele.',
            ],
        ],
        [
            'title' => 'Sua conta',
            'paragraphs' => [
                'Você é responsável por manter sua senha em segredo e por tudo o que acontece na sua conta. Recomendamos ativar a verificação em duas etapas.',
                'Cada conta é pessoal. Seus materiais, cartões e progresso são visíveis só para você.',
            ],
        ],
        [
            'title' => 'Material que você envia',
            'paragraphs' => [
                'Ao enviar um material, você declara que tem direito de usá-lo para o seu estudo pessoal, por exemplo por ser material das suas próprias aulas.',
                'O material continua sendo seu ou de quem detém os direitos sobre ele. Nós só o usamos para gerar o seu conteúdo de estudo.',
                'Não envie material de terceiros sem autorização nem conteúdo ilegal.',
            ],
        ],
        [
            'title' => 'Inteligência artificial',
            'paragraphs' => [
                'O conteúdo gerado pela IA vem só do seu material, mas pode conter erros. Use o botão de reportar quando algo parecer errado.',
                'O texto do material é enviado ao provedor de IA apenas para gerar o seu conteúdo, como descrito na política de privacidade.',
            ],
        ],
        [
            'title' => 'Encerramento',
            'paragraphs' => [
                'Você pode excluir sua conta e todos os seus dados quando quiser. A exclusão é concluída em até 30 dias.',
                'Podemos suspender contas que violem estes termos.',
            ],
        ],
    ],
];
