<?php

return [

    /*
    | Idioma estudado (código ISO 639-1). Hoje é um só para todos; na J1 passa
    | a vir da matrícula do aluno. O nome exibido fica no arquivo de strings
    | do frontend (resources/js/i18n/pt-BR.ts), nunca fixo no código.
    */
    'target_language' => env('FIXA_TARGET_LANGUAGE', 'en'),

    /*
    | Idiomas que o aluno pode estudar hoje. Os demais aparecem como "em breve".
    */
    'available_languages' => ['en'],

    'upcoming_languages' => ['es', 'fr'],

    /*
    | Fusos oferecidos em Configurações (GAM-06). O padrão é America/Sao_Paulo.
    */
    'timezones' => [
        'America/Sao_Paulo',
        'America/Bahia',
        'America/Fortaleza',
        'America/Recife',
        'America/Belem',
        'America/Manaus',
        'America/Cuiaba',
        'America/Porto_Velho',
        'America/Boa_Vista',
        'America/Rio_Branco',
        'America/Noronha',
        'Europe/Lisbon',
        'UTC',
    ],

    /*
    | Versões vigentes dos documentos legais (J1-B7, DAD-01). Subir a versão
    | faz todo aluno aceitar de novo no próximo acesso.
    */
    'legal' => [
        'terms' => '2026-10-23',
        'privacy' => '2026-10-23',
    ],

    /*
    | Inteligência artificial (ADR 0002). "fake" é o padrão até haver chave,
    | e é sempre o usado nos testes.
    */
    'ai' => [
        'provider' => env('FIXA_AI_PROVIDER', 'fake'),

        // Versão vigente de cada prompt em resources/prompts. Mudou o texto, crie v2.
        'prompts' => [
            'lesson_review' => 'v1',
        ],
    ],

    /*
    | Antivírus dos arquivos enviados (UPL-03). "clamav" no Sail e em produção;
    | "fake" só nos testes, que não têm o clamd.
    */
    'antivirus' => [
        'driver' => env('ANTIVIRUS_DRIVER', 'clamav'),
        'host' => env('CLAMAV_HOST', 'clamav'),
        'port' => (int) env('CLAMAV_PORT', 3310),
    ],

];
