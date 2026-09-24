<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class InitialUserSeeder extends Seeder
{
    public function run(): void
    {
        $username = config('mapa_orion.initial_user.username');

        if (User::query()->where('username', $username)->exists()) {
            $this->command?->info(
                "Usuário inicial \"{$username}\" já existe. Nenhuma alteração foi realizada."
            );

            return;
        }

        $password = config('mapa_orion.initial_user.password');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException(
                'A senha inicial configurada deve ser uma string não vazia.'
            );
        }

        $passwordLength = mb_strlen($password);

        if ($passwordLength < 12 || $passwordLength > 1024) {
            throw new RuntimeException(
                'COMANDO_INITIAL_PASSWORD deve possuir entre 12 e 1024 caracteres.'
            );
        }

        $user = new User;

        $user->name = config('mapa_orion.initial_user.name');
        $user->username = $username;
        $user->password = $password;

        $user->save();

        $this->command?->info(
            "Usuário inicial \"{$username}\" criado com sucesso."
        );
    }
}
