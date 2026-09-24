<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OperationalArea implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->isValid($value)) {
            $fail('A área operacional deve ser um GeoJSON Polygon ou MultiPolygon com anéis fechados e coordenadas válidas em longitude e latitude.');
        }
    }

    private function isValid(mixed $geometry): bool
    {
        if (! is_array($geometry) || ! in_array($geometry['type'] ?? null, ['Polygon', 'MultiPolygon'], true)) {
            return false;
        }

        $coordinates = $geometry['coordinates'] ?? null;

        if (! is_array($coordinates) || ! array_is_list($coordinates) || $coordinates === []) {
            return false;
        }

        $polygons = $geometry['type'] === 'Polygon' ? [$coordinates] : $coordinates;

        foreach ($polygons as $polygon) {
            if (! is_array($polygon) || ! array_is_list($polygon) || $polygon === []) {
                return false;
            }

            foreach ($polygon as $ring) {
                if (! is_array($ring) || ! array_is_list($ring) || count($ring) < 4) {
                    return false;
                }

                foreach ($ring as $position) {
                    if (! $this->isPosition($position)) {
                        return false;
                    }
                }

                $first = $ring[0];
                $last = $ring[array_key_last($ring)];

                if ($first[0] != $last[0] || $first[1] != $last[1]) {
                    return false;
                }
            }
        }

        return true;
    }

    private function isPosition(mixed $position): bool
    {
        if (! is_array($position) || ! array_is_list($position) || count($position) !== 2) {
            return false;
        }

        foreach ($position as $coordinate) {
            if ((! is_int($coordinate) && ! is_float($coordinate)) || ! is_finite((float) $coordinate)) {
                return false;
            }
        }

        return $position[0] >= -180 && $position[0] <= 180
            && $position[1] >= -90 && $position[1] <= 90;
    }
}
