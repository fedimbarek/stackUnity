<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Neighborhood;
use App\Models\User;
use App\Services\StatisticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, StatisticsService $stats)
    {
        $neighborhoodId = $request->user()->hasRole('gestionnaire')
            ? $request->user()->neighborhood_id
            : null;

        $from = Carbon::now()->startOfWeek();
        $to = Carbon::now()->endOfWeek();

        $kpis = $stats->compareWithPreviousPeriod($neighborhoodId, $from, $to);
        $byNeighborhood = $stats->byNeighborhood($from, $to);

        return view('admin.dashboard', [
            'totalUsers' => User::count(),
            'totalNeighborhoods' => Neighborhood::count(),
            'kpis' => $kpis,
            'byNeighborhood' => $byNeighborhood,
        ]);
    }
}