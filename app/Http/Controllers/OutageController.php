<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutageReportRequest;
use App\Models\PowerOutage;
use App\Services\OutageAggregationService;
use Illuminate\Http\Request;

class OutageController extends Controller
{
    public function index(Request $request)
    {
        $outages = PowerOutage::with(['neighborhood', 'reports'])
            ->latest('started_at')
            ->when(! $request->user()->hasRole(['admin', 'gestionnaire']), function ($q) use ($request) {
                $q->where('neighborhood_id', $request->user()->neighborhood_id);
            })
            ->paginate(15);

        if ($request->wantsJson()) {
            return response()->json($outages);
        }

        return view('outages.index', compact('outages'));
    }

    public function store(StoreOutageReportRequest $request, OutageAggregationService $service)
    {
        $user = $request->user();
        $neighborhood = $user->neighborhood;

        if (! $neighborhood) {
            $message = "Tu dois d'abord renseigner ton quartier dans ton profil.";

            return $request->wantsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['neighborhood' => $message]);
        }

        $outage = $service->report(
            user: $user,
            neighborhood: $neighborhood,
            latitude: $request->float('latitude'),
            longitude: $request->float('longitude'),
        );

        if ($request->wantsJson()) {
            return response()->json($outage->load('neighborhood'), 201);
        }

        return back()->with('status', 'outage-reported');
    }

    public function show(Request $request, PowerOutage $outage)
    {
        $outage->load(['neighborhood', 'reports.user']);

        return response()->json($outage);
    }

    /** Toujours du JSON — utilisé par la carte Leaflet. */
    public function map()
    {
        $outages = PowerOutage::with('neighborhood:id,name')
            ->whereIn('status', ['reported', 'confirmed'])
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->get(['id', 'neighborhood_id', 'status', 'latitude', 'longitude', 'started_at']);

        return response()->json($outages);
    }

    public function confirm(Request $request, PowerOutage $outage, OutageAggregationService $service)
    {
        $service->confirm($outage);

        return $request->wantsJson()
            ? response()->json($outage->fresh())
            : back()->with('status', 'outage-confirmed');
    }

    public function resolve(Request $request, PowerOutage $outage, OutageAggregationService $service)
    {
        $service->resolve($outage);

        return $request->wantsJson()
            ? response()->json($outage->fresh())
            : back()->with('status', 'outage-resolved');
    }

        public function edit(PowerOutage $outage)
    {
        $neighborhoods = \App\Models\Neighborhood::orderBy('name')->get();

        return view('outages.edit', compact('outage', 'neighborhoods'));
    }

    public function update(Request $request, PowerOutage $outage)
    {
        $data = $request->validate([
            'neighborhood_id' => ['required', 'exists:neighborhoods,id'],
            'status' => ['required', 'in:reported,confirmed,resolved'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $outage->update($data);

        return redirect()->route('outages.index')->with('status', 'outage-updated');
    }

    public function destroy(PowerOutage $outage)
    {
        $outage->delete(); // supprime aussi ses outage_reports (cascadeOnDelete)

        return back()->with('status', 'outage-deleted');
    }
}