<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateUser extends Command
{
    protected $signature = 'users:create {username : Nome de usuário} {--name= : Nome completo}';

    protected $description = 'Cria um usuário do Mapa Orion com senha informada de forma oculta';

    public function handle(): int
    {
        $username = Str::lower(trim($this->argument('username')));
        $name = $this->option('name') ?: $this->ask('Nome completo');

        $validator = Validator::make(compact('username', 'name'), [
            'username' => ['required', 'string', 'max:64', 'regex:/\A[a-z0-9._-]+\z/', 'unique:users,username'],
            'name' => ['required', 'string', 'max:255'],
        ], [
            'username.required' => 'Informe o nome de usuário.',
            'username.max' => 'O nome de usuário deve ter no máximo 64 caracteres.',
            'username.regex' => 'Use apenas letras, números, ponto, hífen ou sublinhado no usuário.',
            'username.unique' => 'Esse nome de usuário já existe.',
            'name.required' => 'Informe o nome completo.',
            'name.string' => 'Informe um nome completo válido.',
            'name.max' => 'O nome completo deve ter no máximo 255 caracteres.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $password = $this->secret('Senha');
        $confirmation = $this->secret('Confirme a senha');
        $passwordValidator = Validator::make([
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'password' => ['required', 'string', 'max:1024', 'confirmed', Password::min(12)],
        ], [
            'password.required' => 'Informe uma senha.',
            'password.max' => 'A senha deve ter no máximo 1024 caracteres.',
            'password.min' => 'A senha deve ter pelo menos 12 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'username' => $username,
            'password' => $password,
        ]);

        $this->info("Usuário {$username} criado com sucesso.");

        return self::SUCCESS;
    }
}
