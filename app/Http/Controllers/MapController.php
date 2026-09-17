<?php

namespace App\Http\Controllers;

use App\Models\PoliceUnit;
use Illuminate\Contracts\View\View;

class MapController extends Controller
{
    public function __invoke(): View
    {
        $units = PoliceUnit::query()
            ->whereNotNull('location')
            ->orderBy('code')
            ->get(['code', 'name', 'acronym', 'unit_type', 'address', 'commander', 'deputy_commander', 'phone', 'email', 'served_localities', 'location', 'officers_count', 'enlisted_count', 'served_population', 'metrics_are_demo'])
            ->append('total_personnel');

        return view('dashboard', compact('units'));
    }
}
