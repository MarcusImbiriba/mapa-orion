<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Database\Seeders\InitialUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class InitialUserSeederTest extends TestCase
{
    use RefreshDatabase;

    private const INITIAL_PASSWORD = 'SenhaInicial@Teste123';

    protected function setUp(): void
    {
        parent::setUp();

        config(['mapa_orion.initial_user' => [
            'name' => 'Comando',
            'username' => 'comando',
            'password' => self::INITIAL_PASSWORD,
        ]]);
    }

    public function test_it_creates_the_initial_user_with_a_hashed_password(): void
    {
        $this->seed(InitialUserSeeder::class);

        $user = User::query()->where('username', 'comando')->sole();

        $this->assertSame('Comando', $user->name);
        $this->assertSame('comando', $user->username);
        $this->assertTrue(Hash::check(self::INITIAL_PASSWORD, $user->password));
        $this->assertNotSame(self::INITIAL_PASSWORD, $user->password);
    }

    public function test_it_uses_the_configured_name_and_username(): void
    {
        config([
            'mapa_orion.initial_user.name' => 'Gestor Inicial',
            'mapa_orion.initial_user.username' => 'gestor',
        ]);

        $this->seed(InitialUserSeeder::class);

        $user = User::query()->sole();

        $this->assertSame('Gestor Inicial', $user->name);
        $this->assertSame('gestor', $user->username);
        $this->assertTrue(Hash::check(self::INITIAL_PASSWORD, $user->password));
    }

    public function test_it_does_not_duplicate_or_update_an_existing_user(): void
    {
        $this->seed(InitialUserSeeder::class);
        $attributes = User::query()->sole()->getAttributes();

        $this->travel(1)->hours();
        $this->seed(InitialUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($attributes, User::query()->sole()->getAttributes());
    }

    public function test_it_does_not_restore_the_initial_name_or_password(): void
    {
        $this->seed(InitialUserSeeder::class);
        $user = User::query()->where('username', 'comando')->sole();
        $newPassword = 'NovaSenha@Teste456';

        $user->name = 'Nome Alterado';
        $user->password = $newPassword;
        $user->save();
        $attributes = $user->getAttributes();

        $this->travel(1)->hours();
        $this->seed(InitialUserSeeder::class);
        $user->refresh();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($attributes, $user->getAttributes());
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Hash::check(self::INITIAL_PASSWORD, $user->password));
    }

    public function test_it_skips_the_configured_existing_user_even_without_an_initial_password(): void
    {
        $user = User::factory()->create(['username' => 'gestor'])->refresh();
        $attributes = $user->getAttributes();
        config([
            'mapa_orion.initial_user.username' => 'gestor',
            'mapa_orion.initial_user.password' => null,
        ]);

        $this->seed(InitialUserSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($attributes, $user->fresh()->getAttributes());
    }

    public function test_it_uses_the_default_password_when_the_environment_variable_is_absent(): void
    {
        $this->loadInitialUserConfiguration(null);

        $this->assertSame('Comando', config('mapa_orion.initial_user.name'));
        $this->assertSame('comando', config('mapa_orion.initial_user.username'));
        $this->assertSame('MapaOrion-2026', config('mapa_orion.initial_user.password'));

        $this->seed(InitialUserSeeder::class);

        $this->assertTrue(Hash::check('MapaOrion-2026', User::query()->sole()->password));
    }

    public function test_it_uses_the_password_from_the_environment_variable(): void
    {
        $this->loadInitialUserConfiguration(self::INITIAL_PASSWORD);

        $this->assertSame(self::INITIAL_PASSWORD, config('mapa_orion.initial_user.password'));

        $this->seed(InitialUserSeeder::class);
        $user = User::query()->sole();

        $this->assertTrue(Hash::check(self::INITIAL_PASSWORD, $user->password));
        $this->assertFalse(Hash::check('MapaOrion-2026', $user->password));
    }

    public function test_an_empty_environment_variable_does_not_activate_the_default_password(): void
    {
        $this->loadInitialUserConfiguration('');

        $this->assertSame('', config('mapa_orion.initial_user.password'));
        $this->expectException(RuntimeException::class);

        $this->seed(InitialUserSeeder::class);
    }

    #[DataProvider('invalidPasswords')]
    public function test_it_rejects_invalid_passwords_when_creating_the_user(?string $password): void
    {
        config(['mapa_orion.initial_user.password' => $password]);

        try {
            $this->seed(InitialUserSeeder::class);
            $this->fail('An invalid initial password must be rejected.');
        } catch (RuntimeException) {
            $this->assertDatabaseCount('users', 0);
        }
    }

    public static function invalidPasswords(): array
    {
        return [
            'missing' => [null],
            'empty' => [''],
            'too short' => [str_repeat('a', 11)],
            'too long' => [str_repeat('a', 1025)],
        ];
    }

    private function loadInitialUserConfiguration(?string $password): void
    {
        // Evaluate the actual config without loading .env or changing this process's environment.
        $process = new Process([
            PHP_BINARY,
            '-r',
            'require $argv[1]; echo json_encode(require $argv[2], JSON_THROW_ON_ERROR);',
            base_path('vendor/autoload.php'),
            config_path('mapa_orion.php'),
        ], base_path(), ['COMANDO_INITIAL_PASSWORD' => $password ?? false]);

        $process->mustRun();

        config(['mapa_orion' => json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR)]);
    }
}
