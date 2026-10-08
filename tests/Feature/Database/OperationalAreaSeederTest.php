<?php

use App\Models\PoliceUnit;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\OperationalAreaSeeder;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

$geometries = json_decode(file_get_contents(__DIR__.'/../../Fixtures/operational-areas.json'), true, flags: JSON_THROW_ON_ERROR);

/**
 * @param  array<string, string>  $files
 */
function prepareOperationalAreaFiles(array $files): void
{
    $disk = Storage::fake('local');

    foreach ($files as $filename => $contents) {
        $disk->put($filename, $contents);
    }

    File::partialMock()->shouldReceive('glob')
        ->with(database_path('seeders/data/operational_areas/*.geojson'))
        ->andReturnUsing(fn (): array|false => glob($disk->path('*.geojson')));
}

test('valid areas are associated by filename code without changing other unit data', function (array $area) {
    $other = PoliceUnit::factory()->create(['code' => 'other-unit', 'name' => '20-bpm']);
    $unit = PoliceUnit::factory()->withLocation()->create(['code' => '20-bpm', 'name' => 'Unidade renomeada']);
    $before = $unit->fresh()->getRawOriginal();
    $otherBefore = $other->fresh()->getRawOriginal();
    prepareOperationalAreaFiles([
        '20-bpm.geojson' => json_encode($area, JSON_THROW_ON_ERROR),
        'ignored.json' => '{',
        'nested/unknown.geojson' => '{',
    ]);

    $this->seed(OperationalAreaSeeder::class);

    expect($unit->fresh()->operational_area)->toBe($area);
    expect($unit->fresh()->getRawOriginal())->toMatchArray(array_diff_key($before, array_flip(['operational_area', 'updated_at'])));
    expect($other->fresh()->getRawOriginal())->toBe($otherBefore);
    $this->assertDatabaseCount('police_units', 2);
})->with([
    'polygon' => [$geometries['valid']['polygon']],
    'multipolygon' => [$geometries['valid']['multipolygon']],
]);

test('an invalid file or association prevents all area writes', function (string $invalidContents, bool $knownUnit, string $message) use ($geometries) {
    PoliceUnit::factory()->create(['code' => 'a-valid']);

    if ($knownUnit) {
        PoliceUnit::factory()->create(['code' => 'z-invalid']);
    }

    $before = PoliceUnit::query()->orderBy('id')->get()
        ->map(fn (PoliceUnit $unit): array => $unit->getRawOriginal())->all();
    prepareOperationalAreaFiles([
        'a-valid.geojson' => json_encode($geometries['valid']['polygon'], JSON_THROW_ON_ERROR),
        'z-invalid.geojson' => $invalidContents,
    ]);
    Event::fake(['eloquent.updated: '.PoliceUnit::class]);

    expect(fn () => $this->seed(OperationalAreaSeeder::class))
        ->toThrow(function (RuntimeException $exception) use ($message): void {
            expect($exception->getMessage())->toContain('z-invalid.geojson', $message);
        });

    expect(PoliceUnit::query()->orderBy('id')->get()
        ->map(fn (PoliceUnit $unit): array => $unit->getRawOriginal())->all())->toBe($before);
    Event::assertNotDispatched('eloquent.updated: '.PoliceUnit::class);
})->with([
    'malformed JSON' => ['{', true, 'JSON inválido'],
    'invalid geometry' => [json_encode($geometries['invalid']['unclosed ring'], JSON_THROW_ON_ERROR), true, 'GeoJSON inválido'],
    'null geometry' => ['null', true, 'GeoJSON inválido'],
    'unknown unit' => [json_encode($geometries['valid']['polygon'], JSON_THROW_ON_ERROR), false, 'Unidade policial inexistente'],
]);

test('an unreadable file fails with its filename and preserves the database', function (string $exceptionClass) use ($geometries) {
    $unit = PoliceUnit::factory()->create(['code' => '20-bpm']);
    $before = $unit->fresh()->getRawOriginal();
    prepareOperationalAreaFiles(['20-bpm.geojson' => json_encode($geometries['valid']['polygon'], JSON_THROW_ON_ERROR)]);
    File::shouldReceive('get')
        ->with(Storage::disk('local')->path('20-bpm.geojson'), false)
        ->andThrow(new $exceptionClass('Unreadable file'));

    expect(fn () => $this->seed(OperationalAreaSeeder::class))
        ->toThrow(function (RuntimeException $exception) use ($exceptionClass): void {
            expect($exception->getMessage())->toContain('20-bpm.geojson', 'arquivo inacessível');
            expect($exception->getPrevious())->toBeInstanceOf($exceptionClass);
        });

    expect($unit->fresh()->getRawOriginal())->toBe($before);
})->with([
    'missing file' => FileNotFoundException::class,
    'read failure' => ErrorException::class,
]);

test('existing areas are preserved and repeated imports leave all attributes and timestamps unchanged', function () use ($geometries) {
    $this->freezeTime();
    $existing = PoliceUnit::factory()->withOperationalArea()->create(['code' => 'existing']);
    PoliceUnit::factory()->create(['code' => 'empty']);
    $existingBefore = $existing->fresh()->getRawOriginal();
    prepareOperationalAreaFiles([
        'existing.geojson' => json_encode($geometries['valid']['multipolygon'], JSON_THROW_ON_ERROR),
        'empty.geojson' => json_encode($geometries['valid']['polygon'], JSON_THROW_ON_ERROR),
    ]);
    $this->travel(1)->day();

    $this->artisan('db:seed', ['--class' => OperationalAreaSeeder::class, '--no-interaction' => true])
        ->expectsOutput('Áreas operacionais: 1 importada(s); 1 já existente(s), preservada(s).')
        ->assertSuccessful();

    expect($existing->fresh()->getRawOriginal())->toBe($existingBefore);
    $before = PoliceUnit::query()->orderBy('id')->get()
        ->map(fn (PoliceUnit $unit): array => $unit->getRawOriginal())->all();
    $this->travel(1)->day();

    $this->artisan('db:seed', ['--class' => OperationalAreaSeeder::class, '--no-interaction' => true])
        ->expectsOutput('Áreas operacionais: 0 importada(s); 2 já existente(s), preservada(s).')
        ->assertSuccessful();

    expect(PoliceUnit::query()->orderBy('id')->get()
        ->map(fn (PoliceUnit $unit): array => $unit->getRawOriginal())->all())->toBe($before);
    $this->travelBack();
});

test('files for already populated areas must still be valid', function () {
    $unit = PoliceUnit::factory()->withOperationalArea()->create(['code' => '20-bpm']);
    $before = $unit->fresh()->getRawOriginal();
    prepareOperationalAreaFiles(['20-bpm.geojson' => '{']);

    expect(fn () => $this->seed(OperationalAreaSeeder::class))
        ->toThrow(RuntimeException::class, '20-bpm.geojson');

    expect($unit->fresh()->getRawOriginal())->toBe($before);
});

test('a persistence failure rolls back earlier imported areas', function () use ($geometries) {
    PoliceUnit::factory()->create(['code' => 'a-valid']);
    PoliceUnit::factory()->create(['code' => 'z-failure']);
    $before = PoliceUnit::query()->orderBy('id')->get()
        ->map(fn (PoliceUnit $unit): array => $unit->getRawOriginal())->all();
    prepareOperationalAreaFiles([
        'a-valid.geojson' => json_encode($geometries['valid']['polygon'], JSON_THROW_ON_ERROR),
        'z-failure.geojson' => json_encode($geometries['valid']['multipolygon'], JSON_THROW_ON_ERROR),
    ]);
    DB::unprepared("CREATE TEMP TRIGGER reject_area_update BEFORE UPDATE ON police_units WHEN NEW.code = 'z-failure' BEGIN SELECT RAISE(ABORT, 'Simulated persistence failure'); END");

    try {
        expect(fn () => $this->seed(OperationalAreaSeeder::class))
            ->toThrow(function (RuntimeException $exception): void {
                expect($exception->getMessage())->toContain('z-failure.geojson', 'importação foi cancelada');
                expect($exception->getPrevious())->toBeInstanceOf(QueryException::class);
            });

        expect(PoliceUnit::query()->orderBy('id')->get()
            ->map(fn (PoliceUnit $unit): array => $unit->getRawOriginal())->all())->toBe($before);
    } finally {
        DB::unprepared('DROP TRIGGER IF EXISTS reject_area_update');
    }
});

test('an empty directory reports zero imports and preserves existing units', function () {
    $unit = PoliceUnit::factory()->withOperationalArea()->create();
    $before = $unit->fresh()->getRawOriginal();
    prepareOperationalAreaFiles([]);

    $this->artisan('db:seed', ['--class' => OperationalAreaSeeder::class, '--no-interaction' => true])
        ->expectsOutput('Áreas operacionais: 0 importada(s); 0 já existente(s), preservada(s).')
        ->assertSuccessful();

    expect($unit->fresh()->getRawOriginal())->toBe($before);
});

test('a file listing failure aborts the import', function () {
    File::partialMock()->shouldReceive('glob')
        ->with(database_path('seeders/data/operational_areas/*.geojson'))
        ->andReturn(false);

    expect(fn () => $this->seed(OperationalAreaSeeder::class))
        ->toThrow(RuntimeException::class, 'Não foi possível listar os arquivos');

    $this->assertDatabaseEmpty('police_units');
});

test('the default seeder creates the initial user and units before importing the canonical area', function () {
    config(['mapa_orion.initial_user' => [
        'name' => 'Comando',
        'username' => 'comando',
        'password' => 'SenhaInicial@Teste123',
    ]]);
    $area = File::json(database_path('seeders/data/operational_areas/20-bpm.geojson'), JSON_THROW_ON_ERROR);

    $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--no-interaction' => true])
        ->expectsOutput('Usuário inicial "comando" criado com sucesso.')
        ->expectsOutput('Unidades: 21 criada(s); 0 já existente(s), preservada(s).')
        ->expectsOutput('Áreas operacionais: 1 importada(s); 0 já existente(s), preservada(s).')
        ->assertSuccessful();

    $this->assertDatabaseHas('users', ['username' => 'comando']);
    $this->assertDatabaseCount('police_units', 21);
    expect(PoliceUnit::query()->where('code', '20-bpm')->sole()->operational_area)->toBe($area);
    expect(PoliceUnit::query()->whereNotNull('operational_area')->pluck('code')->all())->toBe(['20-bpm']);
});
