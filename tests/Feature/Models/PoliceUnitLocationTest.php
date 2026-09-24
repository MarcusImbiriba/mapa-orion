<?php

use App\Models\PoliceUnit;
use App\Rules\GeoJsonPoint;
use Illuminate\Database\Eloquent\JsonEncodingException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

$invalidLocations = [
    'empty string' => [''],
    'empty array' => [[]],
    'scalar' => [false],
    'encoded JSON string' => ['{"type":"Point","coordinates":[-44,-2]}'],
    'missing type' => [['coordinates' => [-44, -2]]],
    'wrong geometry type' => [['type' => 'Polygon', 'coordinates' => [[[-44, -2]]]]],
    'feature wrapper' => [['type' => 'Feature', 'geometry' => ['type' => 'Point', 'coordinates' => [-44, -2]]]],
    'missing coordinates' => [['type' => 'Point']],
    'coordinates as text' => [['type' => 'Point', 'coordinates' => '-44,-2']],
    'missing latitude' => [['type' => 'Point', 'coordinates' => [-44]]],
    'altitude' => [['type' => 'Point', 'coordinates' => [-44, -2, 10]]],
    'associative coordinates' => [['type' => 'Point', 'coordinates' => ['longitude' => -44, 'latitude' => -2]]],
    'numeric string' => [['type' => 'Point', 'coordinates' => ['-44', -2]]],
    'boolean coordinate' => [['type' => 'Point', 'coordinates' => [false, -2]]],
    'null coordinate' => [['type' => 'Point', 'coordinates' => [-44, null]]],
    'longitude below minimum' => [['type' => 'Point', 'coordinates' => [-180.001, -2]]],
    'longitude above maximum' => [['type' => 'Point', 'coordinates' => [180.001, -2]]],
    'latitude below minimum' => [['type' => 'Point', 'coordinates' => [-44, -90.001]]],
    'latitude above maximum' => [['type' => 'Point', 'coordinates' => [-44, 90.001]]],
];

test('valid headquarters points preserve longitude latitude and precision', function (?array $location) {
    $unit = PoliceUnit::factory()->create(['location' => $location]);

    expect($unit->fresh()->location)->toBe($location);
})->with([
    'unknown' => [null],
    'zero' => [['type' => 'Point', 'coordinates' => [0, 0]]],
    'minimum limits' => [['type' => 'Point', 'coordinates' => [-180, -90]]],
    'maximum limits' => [['type' => 'Point', 'coordinates' => [180, 90]]],
    'precise location' => [['type' => 'Point', 'coordinates' => [-44.205670731012624, -2.537604436030353]]],
]);

test('an invalid headquarters point prevents creation of the unit', function (mixed $location) {
    expect(fn () => PoliceUnit::factory()->create(['location' => $location]))
        ->toThrow(function (ValidationException $exception): void {
            expect($exception->errors())->toHaveKey('location');
        });

    $this->assertDatabaseEmpty('police_units');
})->with($invalidLocations);

test('an invalid headquarters point cannot overwrite a unit or its other fields', function (mixed $location) {
    $unit = PoliceUnit::factory()->withLocation()->withOperationalArea()->create();
    $original = $unit->fresh()->getRawOriginal();
    $this->travel(1)->hours();

    expect(fn () => $unit->update(['location' => $location, 'name' => 'Should not be saved']))
        ->toThrow(function (ValidationException $exception): void {
            expect($exception->errors())->toHaveKey('location');
        });

    expect($unit->fresh()->getRawOriginal())->toBe($original);
})->with($invalidLocations);

test('a malformed raw location is not treated as an absent location during saving', function (string $raw) {
    $unit = PoliceUnit::factory()->withLocation()->create();
    $original = $unit->fresh()->getRawOriginal();
    $unit->setRawAttributes(array_replace($original, ['location' => $raw]));

    expect(fn () => $unit->save())->toThrow(function (ValidationException $exception): void {
        expect($exception->errors())->toHaveKey('location');
    });

    expect($unit->fresh()->getRawOriginal())->toBe($original);
})->with(['malformed JSON' => '{', 'empty raw value' => '', 'JSON null instead of SQL null' => 'null']);

test('nonfinite coordinates fail point validation and cannot be persisted', function (float $coordinate) {
    $point = ['type' => 'Point', 'coordinates' => [$coordinate, -2]];
    $errors = [];
    (new GeoJsonPoint)->validate('location', $point, function (string $message) use (&$errors): void {
        $errors[] = $message;
    });
    expect($errors)->toBe([
        'A localização deve ser um GeoJSON Point com exatamente duas coordenadas numéricas finitas: longitude entre -180 e 180 e latitude entre -90 e 90.',
    ]);

    expect(fn () => PoliceUnit::factory()->create(['location' => $point]))->toThrow(JsonEncodingException::class);
    $this->assertDatabaseEmpty('police_units');
})->with(['NaN' => NAN, 'positive infinity' => INF, 'negative infinity' => -INF]);
