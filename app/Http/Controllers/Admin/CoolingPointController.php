<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoolingPointRequest;
use App\Models\CoolingPoint;
use App\Models\CoolingPointType;
use Illuminate\Http\Request;

class CoolingPointController extends Controller
{
    // ── ✨ INDEX AMÉLIORÉ : stats + filtres + pagination ──
    public function index(Request $request)
    {
        $query = CoolingPoint::with('coolingPointType');

        // Filtre par nom
        if ($search = trim((string) $request->input('search'))) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Filtre par type
        if ($typeId = $request->input('type_id')) {
            $query->where('cooling_point_type_id', $typeId);
        }

        $points = $query->latest('id')->paginate(15);

        // Types pour le <select> et les stats
        $types = CoolingPointType::orderBy('name')->get();

        $typesStats = CoolingPointType::withCount('coolingPoints as points_count')
            ->orderBy('name')
            ->get();

        return view('admin.cooling-points.index', compact('points', 'types', 'typesStats'));
    }

    public function create()
    {
        $types = CoolingPointType::all();
        return view('admin.cooling-points.create', compact('types'));
    }

    public function store(StoreCoolingPointRequest $request)
    {
        $point = CoolingPoint::create($request->validated() + ['source' => 'manual']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'point'   => $point->load('coolingPointType'),
            ], 201);
        }

        return redirect()->route('admin.cooling-points.index')->with('success', 'Point créé.');
    }

    public function show(CoolingPoint $coolingPoint)
    {
        return view('admin.cooling-points.show', compact('coolingPoint'));
    }

    public function edit(CoolingPoint $coolingPoint)
    {
        $types = CoolingPointType::all();
        return view('admin.cooling-points.edit', compact('coolingPoint', 'types'));
    }

    public function update(StoreCoolingPointRequest $request, CoolingPoint $coolingPoint)
    {
        $coolingPoint->update($request->validated());
        return redirect()->route('admin.cooling-points.index')->with('success', 'Point modifié.');
    }

    public function destroy(CoolingPoint $coolingPoint)
    {
        $coolingPoint->delete();
        return redirect()->route('admin.cooling-points.index')->with('success', 'Point supprimé.');
    }
}