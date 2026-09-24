<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

test('administrators can create a normalized username without an email', function () {
    $this->artisan('users:create', ['username' => ' Marcus ', '--name' => 'Marcus Imbiriba'])
        ->expectsQuestion('Senha', 'a-long-test-password')
        ->expectsQuestion('Confirme a senha', 'a-long-test-password')
        ->expectsOutput('Usuário marcus criado com sucesso.')
        ->assertSuccessful();

    $user = User::where('username', 'marcus')->sole();
    expect($user->name)->toBe('Marcus Imbiriba');
    expect($user->email)->toBeNull();
    expect($user->password)->not->toBe('a-long-test-password');
    expect(Hash::check('a-long-test-password', $user->password))->toBeTrue();
    $this->assertDatabaseCount('users', 1);
});

test('the command asks for a full name when it is not supplied', function () {
    $this->artisan('users:create', ['username' => 'marcus'])
        ->expectsQuestion('Nome completo', 'Marcus Imbiriba')
        ->expectsQuestion('Senha', 'a-long-test-password')
        ->expectsQuestion('Confirme a senha', 'a-long-test-password')
        ->assertSuccessful();

    $this->assertDatabaseHas('users', ['username' => 'marcus', 'name' => 'Marcus Imbiriba']);
});

test('existing usernames are rejected before asking for a password', function () {
    User::factory()->create(['username' => 'marcus']);

    $this->artisan('users:create', ['username' => 'MARCUS', '--name' => 'Another User'])
        ->expectsOutput('Esse nome de usuário já existe.')
        ->assertFailed();

    $this->assertDatabaseCount('users', 1);
});

test('invalid usernames are rejected without creating an account', function (string $username, string $message) {
    $this->artisan('users:create', ['username' => $username, '--name' => 'Marcus'])
        ->expectsOutput($message)
        ->assertFailed();

    $this->assertDatabaseCount('users', 0);
})->with([
    'email' => ['marcus@example.com', 'Use apenas letras, números, ponto, hífen ou sublinhado no usuário.'],
    'too long' => [str_repeat('a', 65), 'O nome de usuário deve ter no máximo 64 caracteres.'],
    'empty' => [' ', 'Informe o nome de usuário.'],
]);

test('short or unconfirmed passwords do not create an account', function (string $password, string $confirmation, string $message) {
    $this->artisan('users:create', ['username' => 'marcus', '--name' => 'Marcus'])
        ->expectsQuestion('Senha', $password)
        ->expectsQuestion('Confirme a senha', $confirmation)
        ->expectsOutput($message)
        ->assertFailed();

    $this->assertDatabaseCount('users', 0);
})->with([
    'short password' => ['short', 'short', 'A senha deve ter pelo menos 12 caracteres.'],
    'different confirmation' => ['a-long-test-password', 'a-different-password', 'A confirmação da senha não confere.'],
    'empty password' => ['', '', 'Informe uma senha.'],
]);

test('the default seeder creates the configured initial account', function () {
    config(['mapa_orion.initial_user' => [
        'name' => 'Comando',
        'username' => 'comando',
        'password' => 'SenhaInicial@Teste123',
    ]]);

    $this->seed();

    $user = User::where('username', 'comando')->sole();
    expect($user->name)->toBe('Comando');
    expect(Hash::check('SenhaInicial@Teste123', $user->password))->toBeTrue();
    $this->assertDatabaseCount('users', 1);
});
