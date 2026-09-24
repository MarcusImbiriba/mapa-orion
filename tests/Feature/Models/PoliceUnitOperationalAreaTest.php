<?php

use App\Models\PoliceUnit;
use Database\Seeders\PoliceUnitSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

$geometries = json_decode(file_get_contents(__DIR__.'/../../Fixtures/operational-areas.json'), true, flags: JSON_THROW_ON_ERROR);

test('operational geometries preserve their coordinates when stored', function (array $area) {
    $unit = PoliceUnit::factory()->create(['operational_area' => $area]);

    expect($unit->fresh()->operational_area)->toBe($area);
    expect($unit->fresh()->location)->toBeNull();
})->with(array_map(fn ($area) => [$area], $geometries['valid']));

test('invalid operational areas cannot replace existing data', function (mixed $area) {
    $unit = PoliceUnit::factory()->withOperationalArea()->create();
    $original = $unit->fresh()->getRawOriginal();

    expect(fn () => $unit->update(['operational_area' => $area]))
        ->toThrow(function (ValidationException $exception): void {
            expect($exception->errors())->toHaveKey('operational_area');
        });

    expect($unit->fresh()->getRawOriginal())->toBe($original);
})->with(array_map(fn ($area) => [$area], $geometries['invalid']));

test('an area can be added and removed while preserving the unit and its point', function () {
    $unit = PoliceUnit::factory()->withLocation()->create();
    $point = $unit->location;
    $area = PoliceUnit::factory()->withOperationalArea()->make()->operational_area;

    expect($unit->fresh()->operational_area)->toBeNull();
    $unit->update(['operational_area' => $area]);
    expect($unit->fresh()->operational_area)->toBe($area);

    $unit->update(['operational_area' => null]);

    $this->assertModelExists($unit);
    expect($unit->fresh()->operational_area)->toBeNull();
    expect($unit->fresh()->location)->toBe($point);
});

test('seeding preserves a locally assigned operational area', function () {
    $unit = PoliceUnit::factory()->withOperationalArea()->create(['code' => '1-bpm']);
    $original = $unit->fresh()->getRawOriginal();

    $this->seed(PoliceUnitSeeder::class);

    expect($unit->fresh()->getRawOriginal())->toBe($original);
    expect(PoliceUnit::where('code', '2-bpm')->sole()->operational_area)->toBeNull();
});
