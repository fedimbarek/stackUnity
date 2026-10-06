<?php

namespace App\Http\Controllers;

use App\Models\Neighborhood;
use App\Models\PowerOutage;
use App\Models\User;
use App\Models\EmergencyContact;

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
        $priorityContacts = EmergencyContact::active()
        ->where('is_priority', true)
        ->orderBy('name')
        ->limit(4)
        ->get();

        return view('front.home', compact('stats','priorityContacts'));
    }

    public function map()
    {
        return view('front.map');
    }
}