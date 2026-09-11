<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('login renders a username form with one Datastar script and CSRF protection', function () {
    $response = $this->get('/login')
        ->assertSee('Acesse sua conta')
        ->assertSee('name="username"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="_token"', false)
        ->assertSee('data-on:submit=', false)
        ->assertSee('contentType', false)
        ->assertDontSee('data-bind:password', false)
        ->assertDontSee('name="email"', false)
        ->assertDontSee('Criar conta')
        ->assertDontSee('Esqueci minha senha');

    expect(substr_count($response->getContent(), '/vendor/datastar/1.0.3/datastar.js'))->toBe(1);
});

test('guests are redirected away from the protected home page', function () {
    $this->get('/')->assertRedirect('/login');
    $this->assertGuest();
});

test('Datastar guests receive navigation to login instead of protected content', function () {
    $response = $this->get('/', ['Datastar-Request' => 'true'])
        ->assertOk()
        ->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');

    expect($response->streamedContent())->toContain("window.location = '/login'");
    $this->assertGuest();
});

test('a username and password authenticate through a conventional form and renew the session', function () {
    $user = User::factory()->create(['username' => 'marcus']);
    $this->withSession(['existing' => 'value']);
    $sessionId = session()->getId();

    $this->post('/login', ['username' => 'marcus', 'password' => 'password'])
        ->assertRedirect('/')
        ->assertSessionHas('existing', 'value');

    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($sessionId);
});

test('Datastar login authenticates and navigates to the protected home page', function () {
    $user = User::factory()->create(['username' => 'marcus']);

    $response = $this->post('/login', [
        'username' => 'MARCUS',
        'password' => 'password',
    ], ['Datastar-Request' => 'true'])->assertOk()->assertStreamed();

    expect($response->streamedContent())->toContain("window.location = '/'");
    $this->assertAuthenticatedAs($user);
});

test('wrong credentials keep the user logged out and never flash the password', function () {
    User::factory()->create(['username' => 'marcus']);

    $this->post('/login', ['username' => 'marcus', 'password' => 'secret-that-must-not-return'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('username')
        ->assertSessionHas('_old_input.username', 'marcus')
        ->assertSessionMissing('_old_input.password');

    $this->assertGuest();
});

test('Datastar patches a generic credentials error without reflecting the password', function () {
    User::factory()->create(['username' => 'marcus']);

    $response = $this->post('/login', [
        'username' => 'marcus',
        'password' => 'secret-that-must-not-return',
    ], ['Datastar-Request' => 'true'])->assertOk()->assertStreamed();

    expect($response->streamedContent())
        ->toContain('id="login-errors"', 'Usuário ou senha inválidos.')
        ->not->toContain('secret-that-must-not-return');
    $this->assertGuest();
});

test('an unknown username receives the same generic credentials error', function () {
    $response = $this->post('/login', [
        'username' => 'unknown',
        'password' => 'password',
    ], ['Datastar-Request' => 'true'])->assertStreamed();

    expect($response->streamedContent())->toContain('Usuário ou senha inválidos.');
    $this->assertGuest();
});

test('Datastar reports both required fields', function () {
    $response = $this->post('/login', [], ['Datastar-Request' => 'true'])->assertStreamed();

    expect($response->streamedContent())
        ->toContain('Informe seu nome de usuário.', 'Informe sua senha.');
    $this->assertGuest();
});

test('invalid login values receive an actionable validation message', function (array $input, string $message) {
    $response = $this->post('/login', $input, ['Datastar-Request' => 'true'])->assertStreamed();

    expect($response->streamedContent())->toContain($message);
    $this->assertGuest();
})->with([
    'username array' => [['username' => ['marcus'], 'password' => 'password'], 'Informe um nome de usuário válido.'],
    'username too long' => [['username' => str_repeat('a', 65), 'password' => 'password'], 'O nome de usuário deve ter no máximo 64 caracteres.'],
    'email is not a login identifier' => [['username' => 'marcus@example.com', 'password' => 'password'], 'Use apenas letras, números, ponto, hífen ou sublinhado no usuário.'],
    'password array' => [['username' => 'marcus', 'password' => ['password']], 'Informe uma senha válida.'],
    'password too long' => [['username' => 'marcus', 'password' => str_repeat('a', 1025)], 'A senha deve ter no máximo 1024 caracteres.'],
]);

test('conventional validation safely repopulates an invalid username', function () {
    $this->post('/login', ['username' => '<script>alert(1)</script>', 'password' => 'password'])
        ->assertRedirect('/login');

    $this->get('/login')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

test('array usernames are not flashed into the form', function () {
    $this->post('/login', ['username' => ['unexpected'], 'password' => 'password'])
        ->assertSessionHas('_old_input.username', '');

    $this->get('/login')->assertOk();
});

test('login is throttled after five failures and becomes available after one minute', function () {
    $this->freezeTime();
    $user = User::factory()->create(['username' => 'marcus']);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/login', ['username' => $attempt % 2 ? 'MARCUS' : 'marcus', 'password' => 'wrong'])
            ->assertRedirect('/login');
    }

    $this->post('/login', ['username' => 'marcus', 'password' => 'password'])
        ->assertTooManyRequests()
        ->assertHeader('Retry-After', '60')
        ->assertSee('Muitas tentativas. Aguarde 60 segundos e tente novamente.');
    $this->assertGuest();

    $this->travel(61)->seconds();
    $this->post('/login', ['username' => 'marcus', 'password' => 'password'])->assertRedirect('/');
    $this->assertAuthenticatedAs($user);
});

test('Datastar shows throttling feedback without authenticating the user', function () {
    $this->freezeTime();
    User::factory()->create(['username' => 'marcus']);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post('/login', ['username' => 'marcus', 'password' => 'wrong'])->assertRedirect('/login');
    }

    $response = $this->post('/login', [
        'username' => 'marcus',
        'password' => 'password',
    ], ['Datastar-Request' => 'true'])->assertHeader('Retry-After', '60')->assertStreamed();

    expect($response->streamedContent())->toContain('Muitas tentativas. Aguarde 60 segundos e tente novamente.');
    $this->assertGuest();
});

test('successful login clears earlier failed attempts', function () {
    User::factory()->create(['username' => 'marcus']);

    for ($attempt = 0; $attempt < 4; $attempt++) {
        $this->post('/login', ['username' => 'marcus', 'password' => 'wrong']);
    }

    $this->post('/login', ['username' => 'marcus', 'password' => 'password'])->assertRedirect('/');
    $this->post('/logout')->assertRedirect('/login');

    for ($attempt = 0; $attempt < 4; $attempt++) {
        $this->post('/login', ['username' => 'marcus', 'password' => 'wrong']);
    }

    $this->post('/login', ['username' => 'marcus', 'password' => 'password'])->assertRedirect('/');
    $this->assertAuthenticated();
});

test('authenticated users see their escaped name and can access logout', function () {
    $user = User::factory()->create(['name' => '<script>alert(1)</script>']);

    $this->actingAs($user)->get('/')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('Sair');
});

test('an authenticated user is redirected away from the login page', function () {
    $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/');
});

test('posting another users credentials does not switch an authenticated session', function () {
    $user = User::factory()->create();
    User::factory()->create(['username' => 'another']);

    $response = $this->actingAs($user)->post('/login', [
        'username' => 'another',
        'password' => 'password',
    ], ['Datastar-Request' => 'true'])->assertStreamed();

    expect($response->streamedContent())->toContain("window.location = '/'");
    $this->assertAuthenticatedAs($user);
});

test('logout invalidates the session and renews the CSRF token', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession(['private-data' => 'value', '_token' => 'old-token']);
    $sessionId = session()->getId();

    $this->post('/logout')->assertRedirect('/login')->assertSessionMissing('private-data');

    $this->assertGuest();
    expect(session()->getId())->not->toBe($sessionId);
    expect(session()->token())->not->toBe('old-token');
    $this->get('/')->assertRedirect('/login');
});

test('Datastar logout ends the session and navigates to login', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post('/logout', [], ['Datastar-Request' => 'true'])->assertStreamed();

    expect($response->streamedContent())->toContain("window.location = '/login'");
    $this->assertGuest();
});

test('Datastar logout with an expired session navigates to login', function () {
    $response = $this->post('/logout', [], ['Datastar-Request' => 'true'])->assertStreamed();

    expect($response->streamedContent())->toContain("window.location = '/login'");
    $this->assertGuest();
});

test('logout does not accept GET requests', function () {
    $this->get('/logout')->assertMethodNotAllowed();
});

test('public registration and email recovery routes do not exist', function (string $path) {
    $this->get($path)->assertNotFound();
})->with(['/register', '/forgot-password', '/reset-password', '/email/verify']);

test('login requires CSRF protection outside the test environment', function () {
    $this->app->instance('env', 'local');

    $this->withSession(['_token' => 'valid-token'])
        ->post('/login', ['username' => 'marcus', 'password' => 'password'])
        ->assertStatus(419);

    $this->assertGuest();
});

test('a stale CSRF token in Datastar requests navigates to a fresh login page', function () {
    $this->app->instance('env', 'local');

    $response = $this->withSession(['_token' => 'valid-token'])
        ->post('/login', [
            '_token' => 'expired-token',
            'username' => 'marcus',
            'password' => 'password',
        ], ['Datastar-Request' => 'true'])->assertStreamed();

    expect($response->streamedContent())->toContain("window.location = '/login'");
    $this->assertGuest();
});

test('a valid CSRF token permits authentication', function () {
    $this->app->instance('env', 'local');
    $user = User::factory()->create(['username' => 'marcus']);

    $this->withSession(['_token' => 'valid-token'])
        ->post('/login', ['_token' => 'valid-token', 'username' => 'marcus', 'password' => 'password'])
        ->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

test('usernames are normalized and cannot be duplicated through case changes', function () {
    $user = User::factory()->create(['username' => ' Marcus ']);

    expect($user->fresh()->username)->toBe('marcus');
    expect(fn () => User::factory()->create(['username' => 'MARCUS']))->toThrow(QueryException::class);
});

test('unexpected login fields cannot update user attributes', function () {
    $user = User::factory()->create(['username' => 'marcus', 'name' => 'Original']);

    $this->post('/login', [
        'username' => 'marcus', 'password' => 'password', 'name' => 'Changed', 'id' => 999,
    ])->assertRedirect('/');

    expect($user->fresh()->name)->toBe('Original');
    $this->assertAuthenticatedAs($user);
});
