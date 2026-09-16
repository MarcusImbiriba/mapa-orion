<?php

use App\Models\PoliceUnit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

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
