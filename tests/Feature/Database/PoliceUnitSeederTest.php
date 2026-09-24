<?php

use App\Models\PoliceUnit;
use Database\Seeders\PoliceUnitSeeder;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

test('the default seeder installs the current unit catalog with all domain data', function () {
    $this->seed();

    $catalog = File::json(database_path('seeders/data/police_units.json'), JSON_THROW_ON_ERROR);
    $units = PoliceUnit::query()->orderBy('id')->get();

    expect($units->pluck('code')->all())->toBe([
        'qcg-pmma', '1-bpm', '2-bpm', '3-bpm', '4-bpm', '5-bpm', '6-bpm', '7-bpm',
        '8-bpm', '9-bpm', '10-bpm', '11-bpm', '12-bpm', '13-bpm', '14-bpm', '15-bpm',
        '16-bpm', '17-bpm', '18-bpm', '19-bpm', '20-bpm',
    ]);
    expect($units->map(fn (PoliceUnit $unit): array => $unit->only(array_keys($catalog[0])))->all())
        ->toBe($catalog);

    expect($units->firstWhere('code', '1-bpm')->only([
        'name', 'acronym', 'location', 'email', 'served_localities', 'officers_count',
        'enlisted_count', 'served_population', 'region', 'population_source', 'metrics_are_demo',
    ]))->toBe([
        'name' => '1º Batalhão de Polícia Militar',
        'acronym' => '1° BPM',
        'location' => [
            'type' => 'Point',
            'coordinates' => [-44.31167364760949, -2.5571637959302693],
        ],
        'email' => '1bpm@pm.ma.com.br',
        'served_localities' => 'Anjo da Guarda; Vila Embratel; Sá Viana; Vila Nova; Fumacê',
        'officers_count' => 38,
        'enlisted_count' => 302,
        'served_population' => 210000,
        'region' => null,
        'population_source' => null,
        'metrics_are_demo' => true,
    ]);
    expect($units->firstWhere('code', 'qcg-pmma')->only([
        'name', 'acronym', 'unit_type', 'location', 'served_localities', 'officers_count',
        'enlisted_count', 'served_population', 'metrics_are_demo',
    ]))->toBe([
        'name' => 'Quartel do Comando Geral',
        'acronym' => 'QCG',
        'unit_type' => 'COMANDO',
        'location' => [
            'type' => 'Point',
            'coordinates' => [-44.27931417089881, -2.494631631639752],
        ],
        'served_localities' => null,
        'officers_count' => 120,
        'enlisted_count' => 380,
        'served_population' => 1150000,
        'metrics_are_demo' => true,
    ]);

    expect($units->firstWhere('code', '17-bpm')->only(['name', 'location', 'served_localities']))->toBe([
        'name' => '17º Batalhão de Polícia Militar',
        'location' => [
            'type' => 'Point',
            'coordinates' => [-43.879422306744864, -4.407128118429974],
        ],
        'served_localities' => 'Codó; Peritoró; Timbiras',
    ]);
    expect($units->firstWhere('code', '20-bpm')->served_localities)
        ->toBe('Cohab; Cohatrac; Parque Vitório; Alto do Turu; Parque Jair');

    foreach (['16-bpm', '17-bpm', '18-bpm', '19-bpm', '20-bpm'] as $code) {
        expect($units->firstWhere('code', $code)->only([
            'officers_count', 'enlisted_count', 'served_population', 'personnel_reference_date',
            'population_reference_year', 'population_source', 'metrics_are_demo',
        ]))->toBe([
            'officers_count' => null,
            'enlisted_count' => null,
            'served_population' => null,
            'personnel_reference_date' => null,
            'population_reference_year' => null,
            'population_source' => null,
            'metrics_are_demo' => false,
        ]);
    }
});

test('repeating the default seed preserves unit ids attributes and timestamps', function () {
    $this->freezeTime();
    $this->seed();
    $before = PoliceUnit::query()->orderBy('code')->get()
        ->map(fn (PoliceUnit $unit): array => $unit->getRawOriginal())->all();

    $this->travel(1)->day();
    $this->seed();
    $this->travelBack();

    expect(PoliceUnit::query()->orderBy('code')->get()
        ->map(fn (PoliceUnit $unit): array => $unit->getRawOriginal())->all())->toBe($before);
});

test('seeding a partial database preserves revised and additional units while filling missing codes', function () {
    $this->freezeTime();
    $revised = PoliceUnit::factory()->create([
        'code' => '1-bpm',
        'name' => 'Nome revisado localmente',
        'location' => null,
        'phone' => '',
        'officers_count' => 0,
        'enlisted_count' => null,
        'served_population' => 0,
        'metrics_are_demo' => false,
    ]);
    $additional = PoliceUnit::factory()->withLocation()->create(['code' => 'local-unit']);
    $revisedBefore = $revised->fresh()->getRawOriginal();
    $additionalBefore = $additional->fresh()->getRawOriginal();

    $this->travel(1)->day();
    $this->seed(PoliceUnitSeeder::class);
    $this->travelBack();

    $this->assertDatabaseCount('police_units', 22);
    $this->assertDatabaseHas('police_units', ['code' => 'qcg-pmma', 'name' => 'Quartel do Comando Geral']);
    expect($revised->fresh()->getRawOriginal())->toBe($revisedBefore);
    expect($additional->fresh()->getRawOriginal())->toBe($additionalBefore);
});

test('unreadable or malformed catalogs fail without inserting units', function (bool $missing) {
    $path = database_path('seeders/data/police_units.json');
    $read = File::partialMock()->shouldReceive('get')->with($path, false);

    if ($missing) {
        $read->andThrow(new FileNotFoundException('Catalog missing'));
    } else {
        $read->andReturn('[');
    }

    expect(fn () => $this->seed(PoliceUnitSeeder::class))
        ->toThrow(function (RuntimeException $exception) use ($missing): void {
            expect($exception->getPrevious())->toBeInstanceOf($missing ? FileNotFoundException::class : JsonException::class);
            expect($exception->getMessage())->toContain('police_units.json');
        });

    $this->assertDatabaseEmpty('police_units');
})->with(['missing file' => true, 'malformed JSON' => false]);

test('catalogs must be nonempty lists before any units are inserted', function (mixed $catalog) {
    File::partialMock()->shouldReceive('json')
        ->with(database_path('seeders/data/police_units.json'), JSON_THROW_ON_ERROR)
        ->andReturn($catalog);

    expect(fn () => $this->seed(PoliceUnitSeeder::class))
        ->toThrow(function (ValidationException $exception): void {
            expect($exception->errors())->toHaveKey('units');
        });

    $this->assertDatabaseEmpty('police_units');
})->with([
    'empty list' => [[]],
    'null root' => [null],
    'string root' => ['invalid'],
    'object root' => [['units' => []]],
]);

test('invalid catalog records prevent even earlier valid records from being inserted', function (string $field, mixed $value, string $errorField) {
    $catalog = File::json(database_path('seeders/data/police_units.json'), JSON_THROW_ON_ERROR);
    data_set($catalog[20], $field, $value);
    File::partialMock()->shouldReceive('json')
        ->with(database_path('seeders/data/police_units.json'), JSON_THROW_ON_ERROR)
        ->andReturn($catalog);

    expect(fn () => $this->seed(PoliceUnitSeeder::class))
        ->toThrow(function (ValidationException $exception) use ($errorField): void {
            expect($exception->errors())->toHaveKey(rtrim('units.20.'.$errorField, '.'));
        });

    $this->assertDatabaseEmpty('police_units');
})->with([
    'duplicate code' => ['code', '1-bpm', 'code'],
    'missing name' => ['name', null, 'name'],
    'missing acronym' => ['acronym', '', 'acronym'],
    'oversized code' => ['code', str_repeat('a', 65), 'code'],
    'empty location' => ['location', [], 'location'],
    'wrong geometry' => ['location.type', 'Polygon', 'location.type'],
    'missing latitude' => ['location.coordinates', [-44.2], 'location.coordinates.1'],
    'extra coordinate' => ['location.coordinates', [-44.2, -2.5, 0], 'location.coordinates'],
    'coordinates object' => ['location.coordinates', ['longitude' => -44.2, 'latitude' => -2.5], 'location.coordinates'],
    'text coordinate' => ['location.coordinates.0', '-44.2', 'location.coordinates.0'],
    'invalid longitude' => ['location.coordinates.0', -181, 'location.coordinates.0'],
    'invalid latitude' => ['location.coordinates.1', 91, 'location.coordinates.1'],
    'invalid email' => ['email', 'invalid-email', 'email'],
    'negative personnel' => ['officers_count', -1, 'officers_count'],
    'fractional personnel' => ['enlisted_count', 1.5, 'enlisted_count'],
    'population overflow' => ['served_population', 2147483648, 'served_population'],
    'invalid reference date' => ['personnel_reference_date', '2026-02-30', 'personnel_reference_date'],
    'invalid reference year' => ['population_reference_year', 0, 'population_reference_year'],
    'nonboolean demo flag' => ['metrics_are_demo', 'false', 'metrics_are_demo'],
    'unexpected internal id' => ['id', 123, ''],
]);

test('catalogs support unknown locations and real zero values', function (?array $location) {
    $catalog = File::json(database_path('seeders/data/police_units.json'), JSON_THROW_ON_ERROR);
    $unit = $catalog[16];
    $unit['location'] = $location;
    $unit['officers_count'] = 0;
    $unit['enlisted_count'] = 0;
    $unit['served_population'] = 0;
    File::partialMock()->shouldReceive('json')
        ->with(database_path('seeders/data/police_units.json'), JSON_THROW_ON_ERROR)
        ->andReturn([$unit]);

    $this->seed(PoliceUnitSeeder::class);

    $this->assertDatabaseCount('police_units', 1);
    expect(PoliceUnit::query()->sole()->only([
        'location', 'officers_count', 'enlisted_count', 'served_population', 'metrics_are_demo',
    ]))->toBe([
        'location' => $location,
        'officers_count' => 0,
        'enlisted_count' => 0,
        'served_population' => 0,
        'metrics_are_demo' => false,
    ]);
})->with([
    'unknown location' => [null],
    'zero coordinates' => [['type' => 'Point', 'coordinates' => [0, 0]]],
]);

test('a persistence failure rolls back inserted units and preserves existing records', function () {
    $existing = PoliceUnit::factory()->create(['code' => 'local-unit']);
    $before = $existing->fresh()->getRawOriginal();
    DB::unprepared("CREATE TEMP TRIGGER reject_unit_insert BEFORE INSERT ON police_units WHEN NEW.code = '17-bpm' BEGIN SELECT RAISE(ABORT, 'Simulated persistence failure'); END");

    try {
        expect(fn () => $this->seed(PoliceUnitSeeder::class))->toThrow(QueryException::class);

        $this->assertDatabaseCount('police_units', 1);
        expect($existing->fresh()->getRawOriginal())->toBe($before);
    } finally {
        DB::unprepared('DROP TRIGGER IF EXISTS reject_unit_insert');
    }
});
