<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoolingPointRequest;
use App\Models\CoolingPoint;
use App\Models\CoolingPointType;

class CoolingPointController extends Controller
{
    public function index()
    {
        $points = CoolingPoint::with('coolingPointType')->paginate(10);
        return view('admin.cooling-points.index', compact('points'));
    }

    public function create()
    {
        $types = CoolingPointType::all();
        return view('admin.cooling-points.create', compact('types'));
    }

    public function store(StoreCoolingPointRequest $request)
    {
        CoolingPoint::create($request->validated() + ['source' => 'manual']);
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
