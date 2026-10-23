<?php

/*
| Mensagens de validação em pt-BR. Cobre as regras usadas no app; regra nova
| sem mensagem aqui cai no inglês (fallback), então acrescente ao usar.
*/

return [
    'accepted' => 'Para continuar, marque :attribute.',
    'between' => [
        'numeric' => ':Attribute precisa ficar entre :min e :max.',
        'string' => ':Attribute precisa ter entre :min e :max caracteres.',
    ],
    'boolean' => ':Attribute precisa ser sim ou não.',
    'confirmed' => 'A confirmação de :attribute não confere.',
    'current_password' => 'A senha não confere.',
    'email' => 'Informe um e-mail válido.',
    'in' => 'Escolha uma opção válida para :attribute.',
    'integer' => ':Attribute precisa ser um número inteiro.',
    'lowercase' => ':Attribute precisa estar em letras minúsculas.',
    'max' => [
        'numeric' => ':Attribute pode ser no máximo :max.',
        'string' => ':Attribute pode ter no máximo :max caracteres.',
    ],
    'min' => [
        'numeric' => ':Attribute precisa ser pelo menos :min.',
        'string' => ':Attribute precisa ter pelo menos :min caracteres.',
    ],
    'numeric' => ':Attribute precisa ser um número.',
    'password' => [
        'letters' => 'A senha precisa ter pelo menos uma letra.',
        'mixed' => 'A senha precisa ter letras maiúsculas e minúsculas.',
        'numbers' => 'A senha precisa ter pelo menos um número.',
        'symbols' => 'A senha precisa ter pelo menos um símbolo.',
        'uncompromised' => 'Esta senha apareceu em um vazamento de dados. Escolha outra.',
    ],
    'required' => 'Preencha :attribute.',
    'string' => ':Attribute precisa ser um texto.',
    'timezone' => 'Escolha um fuso horário válido.',
    'unique' => 'Este :attribute já está em uso.',

    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'password' => 'senha',
        'current_password' => 'senha atual',
        'code' => 'código',
        'recovery_code' => 'código de recuperação',
        'terms' => 'o aceite dos termos',
        'daily_goal_minutes' => 'meta diária',
        'timezone' => 'fuso horário',
        'target_language' => 'idioma estudado',
    ],
];
