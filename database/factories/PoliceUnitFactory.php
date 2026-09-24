<?php

namespace Database\Factories;

use App\Models\PoliceUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PoliceUnit>
 */
class PoliceUnitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('unit-########'),
            'name' => 'Unidade de Teste '.fake()->numerify('####'),
            'acronym' => fake()->bothify('UP-####'),
            'unit_type' => null,
            'region' => null,
            'address' => null,
            'location' => null,
            'operational_area' => null,
        ];
    }

    public function withOperationalArea(): static
    {
        return $this->state(fn (array $attributes): array => [
            'operational_area' => [
                'type' => 'Polygon',
                'coordinates' => [[[-44.32, -2.55], [-44.28, -2.55], [-44.28, -2.51], [-44.32, -2.55]]],
            ],
        ]);
    }

    public function withLocation(): static
    {
        return $this->state(fn (array $attributes): array => [
            'location' => [
                'type' => 'Point',
                'coordinates' => [-44.2797, -2.4968],
            ],
        ]);
    }
}
