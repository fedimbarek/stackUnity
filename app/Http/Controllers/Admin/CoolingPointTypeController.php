<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoolingPointTypeRequest;
use App\Models\CoolingPointType;
use Illuminate\Support\Str;

class CoolingPointTypeController extends Controller
{
    public function index()
    {
        $types = CoolingPointType::withCount('coolingPoints')->paginate(10);
        return view('admin.cooling-point-types.index', compact('types'));
    }

    public function create()
    {
        return view('admin.cooling-point-types.create');
    }

    public function store(StoreCoolingPointTypeRequest $request)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        CoolingPointType::create($data);
        return redirect()->route('admin.cooling-point-types.index')->with('success', 'Type créé avec succès.');
    }

    public function show(CoolingPointType $coolingPointType)
    {
        $coolingPointType->load('coolingPoints');
        return view('admin.cooling-point-types.show', compact('coolingPointType'));
    }

    public function edit(CoolingPointType $coolingPointType)
    {
        return view('admin.cooling-point-types.edit', compact('coolingPointType'));
    }

    public function update(StoreCoolingPointTypeRequest $request, CoolingPointType $coolingPointType)
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);
        $coolingPointType->update($data);
        return redirect()->route('admin.cooling-point-types.index')->with('success', 'Type modifié.');
    }

    public function destroy(CoolingPointType $coolingPointType)
    {
        $coolingPointType->delete();
        return redirect()->route('admin.cooling-point-types.index')->with('success', 'Type supprimé.');
    }
}
