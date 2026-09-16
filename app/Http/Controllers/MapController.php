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
            ->get(['code', 'name', 'acronym', 'location']);

        return view('dashboard', compact('units'));
    }
}
