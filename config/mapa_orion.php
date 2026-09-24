<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Usuário Inicial Comando
    |--------------------------------------------------------------------------
    |
    | Define os dados utilizados apenas na criação do usuário inicial do sistema.
    |
    */

    'initial_user' => [
        'name' => 'Comando',
        'username' => 'comando',
        'password' => env('COMANDO_INITIAL_PASSWORD', 'MapaOrion-2026'),
    ],
];
