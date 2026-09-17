<?php

namespace App\Models;

use Database\Factories\PoliceUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

#[Fillable(['code', 'name', 'acronym', 'unit_type', 'region', 'address', 'location', 'commander', 'deputy_commander', 'phone', 'email', 'served_localities', 'officers_count', 'enlisted_count', 'served_population', 'personnel_reference_date', 'population_reference_year', 'population_source', 'metrics_are_demo'])]
class PoliceUnit extends Model
{
    /** @use HasFactory<PoliceUnitFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (PoliceUnit $unit): void {
            Validator::make($unit->getAttributes(), [
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
            'officers_count' => 'integer',
            'enlisted_count' => 'integer',
            'served_population' => 'integer',
            'personnel_reference_date' => 'date:Y-m-d',
            'population_reference_year' => 'integer',
            'metrics_are_demo' => 'boolean',
        ];
    }
}
