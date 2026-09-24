<?php

namespace App\Http\Controllers;

use App\Models\PoliceUnit;
use Illuminate\Contracts\View\View;

class MapController extends Controller
{
    public function __invoke(): View
    {
        $units = PoliceUnit::query()
            ->get(['code', 'name', 'acronym', 'unit_type', 'address', 'commander', 'deputy_commander', 'phone', 'email', 'served_localities', 'location', 'operational_area', 'officers_count', 'enlisted_count', 'served_population', 'metrics_are_demo'])
            ->append('total_personnel')
            ->sortBy(fn (PoliceUnit $unit): string => $unit->code === 'qcg-pmma' ? '' : $unit->code, SORT_NATURAL)
            ->values();

        return view('dashboard', compact('units'));
    }
}
