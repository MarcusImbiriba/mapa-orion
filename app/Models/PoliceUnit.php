<?php

namespace App\Models;

use App\Rules\GeoJsonPoint;
use App\Rules\OperationalArea;
use Database\Factories\PoliceUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Fillable(['code', 'name', 'acronym', 'unit_type', 'region', 'address', 'location', 'operational_area', 'commander', 'deputy_commander', 'phone', 'email', 'served_localities', 'officers_count', 'enlisted_count', 'served_population', 'personnel_reference_date', 'population_reference_year', 'population_source', 'metrics_are_demo'])]
class PoliceUnit extends Model
{
    /** @use HasFactory<PoliceUnitFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (PoliceUnit $unit): void {
            $attributes = $unit->getAttributes();

            Validator::make(array_replace($attributes, [
                'location' => $unit->location,
                'operational_area' => $unit->operational_area,
            ]), [
                'location' => ['nullable', Rule::requiredIf(($attributes['location'] ?? null) !== null), new GeoJsonPoint],
                'operational_area' => ['nullable', Rule::requiredIf($unit->operational_area !== null), new OperationalArea],
                'officers_count' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
                'enlisted_count' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
                'served_population' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
                'population_reference_year' => ['nullable', 'integer', 'between:1,9999'],
                'population_source' => ['nullable', 'string', 'max:255'],
                'metrics_are_demo' => ['boolean'],
            ])->validate();
        });
    }

    /**
     * @return Attribute<int|null, never>
     */
    protected function totalPersonnel(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->officers_count === null || $this->enlisted_count === null
                ? null
                : $this->officers_count + $this->enlisted_count,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'location' => 'array',
            'operational_area' => 'array',
            'officers_count' => 'integer',
            'enlisted_count' => 'integer',
            'served_population' => 'integer',
            'personnel_reference_date' => 'date:Y-m-d',
            'population_reference_year' => 'integer',
            'metrics_are_demo' => 'boolean',
        ];
    }
}
