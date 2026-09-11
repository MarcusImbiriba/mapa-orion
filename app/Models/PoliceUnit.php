<?php

namespace App\Models;

use Database\Factories\PoliceUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'acronym', 'unit_type', 'region', 'address', 'location'])]
class PoliceUnit extends Model
{
    /** @use HasFactory<PoliceUnitFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'location' => 'array',
        ];
    }
}
