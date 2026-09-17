<?php

use App\Models\PoliceUnit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

test('a police unit can be stored with only its identity and without an operational area', function () {
    $attributes = PoliceUnit::factory()->raw([
        'code' => 'qg-pmma',
        'name' => 'Quartel General da PMMA',
        'acronym' => 'QCG PMMA',
    ]);

    $unit = PoliceUnit::create(array_intersect_key($attributes, array_flip(['code', 'name', 'acronym'])));

    $this->assertModelExists($unit);
    expect($unit->fresh()->toArray())->toMatchArray([
        'code' => 'qg-pmma',
        'name' => 'Quartel General da PMMA',
        'acronym' => 'QCG PMMA',
        'unit_type' => null,
        'region' => null,
        'address' => null,
        'location' => null,
        'commander' => null,
        'deputy_commander' => null,
        'phone' => null,
        'email' => null,
        'served_localities' => null,
    ]);
});

test('a police unit preserves a GeoJSON point and descriptive fields when stored', function () {
    $attributes = PoliceUnit::factory()->raw([
        'unit_type' => 'Batalhão Territorial',
        'region' => 'São Luís',
        'address' => 'Endereço de teste',
        'location' => [
            'type' => 'Point',
            'coordinates' => [-44.205670731012624, -2.537604436030353],
        ],
    ]);

    $unit = PoliceUnit::create($attributes);

    $this->assertModelExists($unit);
    expect($unit->fresh()->toArray())->toMatchArray($attributes);
});

test('a location can be added to an existing police unit', function () {
    $unit = PoliceUnit::factory()->create();

    $unit->update([
        'location' => [
            'type' => 'Point',
            'coordinates' => [-44.321, -2.564],
        ],
    ]);

    expect($unit->fresh()->location)->toBe([
        'type' => 'Point',
        'coordinates' => [-44.321, -2.564],
    ]);
});

test('a location can be removed without deleting the police unit', function () {
    $unit = PoliceUnit::factory()->withLocation()->create();

    $unit->update(['location' => null]);

    $this->assertDatabaseHas('police_units', [
        'id' => $unit->id,
        'code' => $unit->code,
        'location' => null,
    ]);
});

test('editing the unit name preserves its location', function () {
    $unit = PoliceUnit::factory()->withLocation()->create();

    $unit->update(['name' => 'Nome atualizado']);

    expect($unit->fresh()->toArray())->toMatchArray([
        'name' => 'Nome atualizado',
        'location' => [
            'type' => 'Point',
            'coordinates' => [-44.2797, -2.4968],
        ],
    ]);
});

test('police unit codes must be unique', function () {
    PoliceUnit::factory()->create(['code' => 'bpm-1']);

    expect(fn () => PoliceUnit::factory()->create(['code' => 'bpm-1']))
        ->toThrow(QueryException::class);

    $this->assertDatabaseCount('police_units', 1);
});

test('the database requires each identity field', function (string $field) {
    $attributes = PoliceUnit::factory()->raw();
    unset($attributes[$field]);

    expect(fn () => PoliceUnit::create($attributes))->toThrow(QueryException::class);

    $this->assertDatabaseCount('police_units', 0);
})->with(['code', 'name', 'acronym']);

test('a police unit preserves command contacts and served localities', function () {
    $attributes = PoliceUnit::factory()->raw([
        'commander' => 'Comandante de teste',
        'deputy_commander' => 'Subcomandante de teste',
        'phone' => '(98) 3000-0000 / 0001',
        'email' => 'unidade@example.com',
        'served_localities' => 'Bairro de teste (parcial — trecho norte); Cidade de teste',
    ]);

    $unit = PoliceUnit::create($attributes);

    expect($unit->fresh()->toArray())->toMatchArray($attributes);
});

test('missing details can be completed later without changing identity or location', function () {
    $unit = PoliceUnit::factory()->withLocation()->create();
    $identity = $unit->only(['id', 'code', 'name', 'acronym', 'location']);
    $details = [
        'commander' => 'Comandante de teste',
        'deputy_commander' => 'Subcomandante de teste',
        'phone' => '(98) 3000-0000',
        'email' => 'unidade@example.com',
        'served_localities' => 'Bairro de teste; Cidade de teste',
    ];

    $unit->update($details);

    expect($unit->fresh()->toArray())->toMatchArray([...$identity, ...$details]);
});

test('optional details can be cleared without deleting the police unit', function () {
    $unit = PoliceUnit::factory()->create([
        'commander' => 'Comandante de teste',
        'deputy_commander' => 'Subcomandante de teste',
        'phone' => '(98) 3000-0000',
        'email' => 'unidade@example.com',
        'served_localities' => 'Cidade de teste',
    ]);

    $unit->update([
        'commander' => null,
        'deputy_commander' => null,
        'phone' => null,
        'email' => null,
        'served_localities' => null,
    ]);

    $this->assertDatabaseHas('police_units', [
        'id' => $unit->id,
        'commander' => null,
        'deputy_commander' => null,
        'phone' => null,
        'email' => null,
        'served_localities' => null,
    ]);
});

test('current metrics preserve zero and unknown values when calculating personnel', function (?int $officers, ?int $enlisted, ?int $total) {
    $unit = PoliceUnit::factory()->create([
        'officers_count' => $officers,
        'enlisted_count' => $enlisted,
        'served_population' => null,
    ])->fresh();

    expect($unit->total_personnel)->toBe($total);
    expect($unit->served_population)->toBeNull();
})->with([
    'known personnel' => [45, 245, 290],
    'no personnel assigned' => [0, 0, 0],
    'no officers' => [0, 80, 80],
    'no enlisted' => [12, 0, 12],
    'unknown officers' => [null, 245, null],
    'unknown enlisted' => [45, null, null],
    'both unknown' => [null, null, null],
]);

test('current metrics can be replaced and cleared without retaining an outdated total', function () {
    $unit = PoliceUnit::factory()->create([
        'officers_count' => 45,
        'enlisted_count' => 245,
        'served_population' => 275000,
        'metrics_are_demo' => true,
    ]);

    $unit->update([
        'officers_count' => 50,
        'enlisted_count' => 260,
        'served_population' => 0,
        'personnel_reference_date' => '2026-09-16',
        'population_reference_year' => 2026,
        'population_source' => 'Fonte de teste',
        'metrics_are_demo' => false,
    ]);

    expect($unit->fresh()->toArray())->toMatchArray([
        'officers_count' => 50,
        'enlisted_count' => 260,
        'served_population' => 0,
        'personnel_reference_date' => '2026-09-16',
        'population_reference_year' => 2026,
        'population_source' => 'Fonte de teste',
        'metrics_are_demo' => false,
    ]);
    expect($unit->total_personnel)->toBe(310);

    $unit->update(['officers_count' => null, 'served_population' => null]);

    expect($unit->fresh()->total_personnel)->toBeNull();
    $this->assertDatabaseHas('police_units', ['id' => $unit->id, 'officers_count' => null, 'served_population' => null]);
});

test('invalid counts cannot replace existing metrics', function (string $field, mixed $value) {
    $unit = PoliceUnit::factory()->create([
        'officers_count' => 45,
        'enlisted_count' => 245,
        'served_population' => 275000,
    ]);

    expect(fn () => $unit->update([$field => $value]))
        ->toThrow(ValidationException::class);

    $this->assertDatabaseHas('police_units', [
        'id' => $unit->id,
        'officers_count' => 45,
        'enlisted_count' => 245,
        'served_population' => 275000,
    ]);
})->with(['officers_count', 'enlisted_count', 'served_population'])
    ->with(['negative' => -1, 'fraction' => 1.5, 'text' => 'unknown', 'too large' => 2147483648]);
