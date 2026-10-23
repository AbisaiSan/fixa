<?php

/*
| Política de privacidade. RASCUNHO: revisar com advogado antes de qualquer
| usuário externo. Mudou o texto, suba a versão em config/fixa.php.
*/

return [
    'title' => 'Política de privacidade',
    'draft' => true,
    'sections' => [
        [
            'title' => 'Dados que coletamos',
            'paragraphs' => [
                'Dados da conta: nome, e-mail e senha (guardada só como hash).',
                'Dados de estudo: materiais que você envia, conteúdo gerado a partir deles, respostas, progresso e preferências.',
                'Dados técnicos: endereço IP e registros de segurança, como entradas na conta, sem o conteúdo dos seus estudos.',
            ],
        ],
        [
            'title' => 'Para que usamos',
            'paragraphs' => [
                'Para gerar e organizar o seu estudo, manter sua conta segura e cumprir obrigações legais. Não vendemos seus dados nem os usamos para publicidade.',
            ],
        ],
        [
            'title' => 'Inteligência artificial e outros fornecedores',
            'paragraphs' => [
                'O texto dos materiais é enviado ao provedor de IA só para gerar o seu conteúdo. Registramos qual provedor processou cada pedido, sem guardar o texto nos registros.',
                'Arquivos ficam em armazenamento privado e só são acessados por links temporários e autenticados.',
            ],
        ],
        [
            'title' => 'Quanto tempo guardamos',
            'paragraphs' => [
                'Guardamos seus dados enquanto a conta existir. Ao excluir a conta, apagamos tudo em até 30 dias.',
            ],
        ],
        [
            'title' => 'Seus direitos',
            'paragraphs' => [
                'Pela LGPD, você pode acessar, corrigir, exportar e excluir seus dados, e revogar consentimentos. A exportação e a exclusão ficam em Configurações.',
            ],
        ],
    ],
];
