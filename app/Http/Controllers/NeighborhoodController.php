<?php

namespace App\Http\Controllers;

use App\Http\Requests\NeighborhoodRequest;
use App\Models\Neighborhood;

class NeighborhoodController extends Controller
{
    public function index()
    {
        $neighborhoods = Neighborhood::withCount(['users', 'powerOutages'])
            ->orderBy('name')
            ->paginate(10);

        return view('neighborhoods.index', compact('neighborhoods'));
    }

    public function create()
    {
        return view('neighborhoods.create');
    }

    public function store(NeighborhoodRequest $request)
    {
        Neighborhood::create($request->validated());

        return redirect()->route('neighborhoods.index')->with('status', 'neighborhood-created');
    }

    public function show(Neighborhood $neighborhood)
    {
        $neighborhood->load([
            'users' => fn ($q) => $q->with('roles'),
            'powerOutages' => fn ($q) => $q->latest()->limit(5),
        ]);

        return view('neighborhoods.show', compact('neighborhood'));
    }

    public function edit(Neighborhood $neighborhood)
    {
        return view('neighborhoods.edit', compact('neighborhood'));
    }

    public function update(NeighborhoodRequest $request, Neighborhood $neighborhood)
    {
        $neighborhood->update($request->validated());

        return redirect()->route('neighborhoods.index')->with('status', 'neighborhood-updated');
    }

    public function destroy(Neighborhood $neighborhood)
    {
        if ($neighborhood->users()->exists()) {
            return back()->withErrors(['delete' => 'Impossible de supprimer : des utilisateurs sont rattachés à ce quartier.']);
        }

        $neighborhood->delete();

        return redirect()->route('neighborhoods.index')->with('status', 'neighborhood-deleted');
    }
}