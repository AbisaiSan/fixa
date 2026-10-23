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

];
