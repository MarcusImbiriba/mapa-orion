<?php

namespace Database\Seeders;

use App\Models\PoliceUnit;
use App\Rules\OperationalArea;
use ErrorException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use JsonException;
use RuntimeException;
use Throwable;

class OperationalAreaSeeder extends Seeder
{
    public function run(): void
    {
        $files = File::glob(database_path('seeders/data/operational_areas/*.geojson'));

        if ($files === false) {
            throw new RuntimeException('Não foi possível listar os arquivos de áreas operacionais.');
        }

        sort($files);

        /** @var list<array{code: string, file: string, geometry: array<string, mixed>}> $areas */
        $areas = [];

        foreach ($files as $path) {
            $filename = basename($path);

            try {
                $geometry = File::json($path, JSON_THROW_ON_ERROR);
            } catch (ErrorException|FileNotFoundException|JsonException $exception) {
                throw new RuntimeException("Não foi possível ler o GeoJSON {$filename}: arquivo inacessível ou JSON inválido.", previous: $exception);
            }

            $validator = Validator::make(['operational_area' => $geometry], [
                'operational_area' => ['required', new OperationalArea],
            ]);

            if ($validator->fails()) {
                throw new RuntimeException("GeoJSON inválido em {$filename}: ".$validator->errors()->first('operational_area'));
            }

            $areas[] = [
                'code' => pathinfo($path, PATHINFO_FILENAME),
                'file' => $filename,
                'geometry' => $geometry,
            ];
        }

        $imported = DB::transaction(function () use ($areas): int {
            $units = [];

            foreach ($areas as $area) {
                $unit = PoliceUnit::query()->where('code', $area['code'])->lockForUpdate()->first();

                if ($unit === null) {
                    throw new RuntimeException("Unidade policial inexistente para {$area['file']} (código: {$area['code']}).");
                }

                $units[$area['code']] = $unit;
            }

            $imported = 0;

            foreach ($areas as $area) {
                $unit = $units[$area['code']];

                if ($unit->getRawOriginal('operational_area') !== null) {
                    continue;
                }

                try {
                    $unit->operational_area = $area['geometry'];

                    if (! $unit->save()) {
                        throw new RuntimeException('A gravação foi cancelada.');
                    }
                } catch (Throwable $exception) {
                    throw new RuntimeException("Não foi possível gravar a área de {$area['file']}. A importação foi cancelada.", previous: $exception);
                }

                $imported++;
            }

            return $imported;
        });

        $this->command?->info(sprintf(
            'Áreas operacionais: %d importada(s); %d já existente(s), preservada(s).',
            $imported,
            count($areas) - $imported,
        ));
    }
}
