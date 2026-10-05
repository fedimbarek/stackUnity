<?php

namespace App\Http\Controllers;

use App\Models\Neighborhood;
use App\Models\PowerOutage;
use App\Models\User;

class FrontOfficeController extends Controller
{
    public function home()
    {
        $stats = [
            'neighborhoods' => Neighborhood::count(),
            'outages_tracked' => PowerOutage::count(),
            'outages_resolved' => PowerOutage::where('status', 'resolved')->count(),
            'residents' => User::role('resident')->count(),
        ];

        return view('front.home', compact('stats'));
    }

    public function map()
    {
        return view('front.map');
    }
}