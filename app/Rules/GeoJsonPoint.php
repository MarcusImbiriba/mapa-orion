<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GeoJsonPoint implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->isValid($value)) {
            $fail('A localização deve ser um GeoJSON Point com exatamente duas coordenadas numéricas finitas: longitude entre -180 e 180 e latitude entre -90 e 90.');
        }
    }

    private function isValid(mixed $geometry): bool
    {
        if (! is_array($geometry) || ($geometry['type'] ?? null) !== 'Point') {
            return false;
        }

        $coordinates = $geometry['coordinates'] ?? null;

        if (! is_array($coordinates) || ! array_is_list($coordinates) || count($coordinates) !== 2) {
            return false;
        }

        foreach ($coordinates as $coordinate) {
            if ((! is_int($coordinate) && ! is_float($coordinate)) || ! is_finite((float) $coordinate)) {
                return false;
            }
        }

        return $coordinates[0] >= -180 && $coordinates[0] <= 180
            && $coordinates[1] >= -90 && $coordinates[1] <= 90;
    }
}
