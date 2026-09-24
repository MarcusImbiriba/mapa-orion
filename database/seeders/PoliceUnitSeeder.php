<?php

namespace Database\Seeders;

use App\Models\PoliceUnit;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use JsonException;
use RuntimeException;

class PoliceUnitSeeder extends Seeder
{
    public function run(): void
    {
        try {
            $units = File::json(database_path('seeders/data/police_units.json'), JSON_THROW_ON_ERROR);
        } catch (FileNotFoundException|JsonException $exception) {
            throw new RuntimeException('Não foi possível ler o catálogo database/seeders/data/police_units.json.', previous: $exception);
        }

        $validated = Validator::make(['units' => $units], [
            'units' => ['required', 'array', 'list', 'min:1'],
            'units.*' => ['required', 'array:code,name,acronym,unit_type,region,address,location,commander,deputy_commander,phone,email,served_localities,officers_count,enlisted_count,served_population,personnel_reference_date,population_reference_year,population_source,metrics_are_demo'],
            'units.*.code' => ['required', 'string', 'max:64', 'distinct:strict'],
            'units.*.name' => ['required', 'string', 'max:255'],
            'units.*.acronym' => ['required', 'string', 'max:64'],
            'units.*.unit_type' => ['nullable', 'string', 'max:255'],
            'units.*.region' => ['nullable', 'string', 'max:255'],
            'units.*.address' => ['nullable', 'string'],
            'units.*.location' => ['present', 'nullable', 'array:type,coordinates', 'size:2'],
            'units.*.location.type' => ['required_with:units.*.location', 'in:Point'],
            'units.*.location.coordinates' => ['required_with:units.*.location', 'array', 'list', 'size:2'],
            'units.*.location.coordinates.0' => ['required_with:units.*.location', 'numeric:strict', 'between:-180,180'],
            'units.*.location.coordinates.1' => ['required_with:units.*.location', 'numeric:strict', 'between:-90,90'],
            'units.*.commander' => ['nullable', 'string', 'max:255'],
            'units.*.deputy_commander' => ['nullable', 'string', 'max:255'],
            'units.*.phone' => ['nullable', 'string'],
            'units.*.email' => ['nullable', 'string', 'email', 'max:255'],
            'units.*.served_localities' => ['nullable', 'string'],
            'units.*.officers_count' => ['nullable', 'integer:strict', 'min:0', 'max:2147483647'],
            'units.*.enlisted_count' => ['nullable', 'integer:strict', 'min:0', 'max:2147483647'],
            'units.*.served_population' => ['nullable', 'integer:strict', 'min:0', 'max:2147483647'],
            'units.*.personnel_reference_date' => ['nullable', 'date_format:Y-m-d'],
            'units.*.population_reference_year' => ['nullable', 'integer:strict', 'between:1,9999'],
            'units.*.population_source' => ['nullable', 'string', 'max:255'],
            'units.*.metrics_are_demo' => ['required', 'boolean:strict'],
        ])->validate();

        $created = DB::transaction(function () use ($validated): int {
            $created = 0;

            foreach ($validated['units'] as $unit) {
                $record = PoliceUnit::query()->firstOrCreate(['code' => $unit['code']], $unit);

                if ($record->wasRecentlyCreated) {
                    $created++;
                }
            }

            return $created;
        });

        $this->command?->info(sprintf(
            'Unidades: %d criada(s); %d já existente(s), preservada(s).',
            $created,
            count($validated['units']) - $created,
        ));
    }
}
