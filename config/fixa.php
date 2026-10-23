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

    // Nome dos idiomas usado nos prompts da IA (o frontend tem o próprio arquivo de strings).
    'language_names' => ['en' => 'inglês', 'es' => 'espanhol', 'fr' => 'francês'],

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

        // Teto mensal de gasto com IA, em dólares (IAS-03). Alerta em 80%.
        'monthly_budget_usd' => (float) env('FIXA_AI_MONTHLY_BUDGET_USD', 5),

        // Preço por milhão de tokens, para estimar o custo (IAS-03). Confira e
        // atualize com a tabela oficial do provedor; modelo sem preço custa 0.
        'prices' => [
            'gemini-3.5-flash-lite' => ['input' => env('PRICE_GEMINI_LITE_INPUT', 0), 'output' => env('PRICE_GEMINI_LITE_OUTPUT', 0)],
            'gemini-3.8-flash' => ['input' => env('PRICE_GEMINI_FLASH_INPUT', 0), 'output' => env('PRICE_GEMINI_FLASH_OUTPUT', 0)],
        ],

        // Novas gerações por aula pedidas pelo aluno, somando "regenerar" e
        // "tentar de novo" (REV-04). A primeira geração não conta.
        'max_regenerations_per_lesson' => (int) env('FIXA_AI_MAX_REGENERATIONS', 3),

        // Versão vigente de cada prompt em resources/prompts. Mudou o texto, crie v2.
        'prompts' => [
            'lesson_review' => 'v1',
            'cards' => 'v2',
            'word_details' => 'v1',
            'activities' => 'v1',
        ],

        // Pedidos de IA feitos pelo aluno por dia, como correções e
        // "completar com IA" no vocabulário (ATV-05).
        'daily_student_limit' => (int) env('FIXA_AI_DAILY_STUDENT_LIMIT', 30),

        // Cartões novos por aula (FLA-02).
        'max_cards_per_lesson' => 30,
    ],

    /*
    | Flashcards (FLA-04): cartões novos por dia, além dos vencidos.
    */
    'cards' => [
        'new_per_day' => (int) env('FIXA_NEW_CARDS_PER_DAY', 15),
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
