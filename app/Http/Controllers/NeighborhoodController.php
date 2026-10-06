<?php

namespace App\Http\Controllers;

use App\Http\Requests\NeighborhoodRequest;
use App\Models\Neighborhood;
use Illuminate\Http\Request;

class NeighborhoodController extends Controller
{
    public function index(Request $request)
    {
        // Colonnes autorisées pour le tri (évite l'injection dans orderBy)
        $sorts = ['name', 'city', 'users_count', 'power_outages_count'];
        $sort = in_array($request->input('sort'), $sorts, true) ? $request->input('sort') : 'name';
        $dir = $request->input('dir') === 'desc' ? 'desc' : 'asc';

        $neighborhoods = Neighborhood::withCount(['users', 'powerOutages'])
            // Recherche (nom ou ville)
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = trim($request->input('search'));
                $q->where(function ($qq) use ($s) {
                    $qq->where('name', 'like', "%{$s}%")
                       ->orWhere('city', 'like', "%{$s}%");
                });
            })
            // Filtre par ville
            ->when($request->filled('city'), fn ($q) => $q->where('city', $request->input('city')))
            // Filtre résidents
            ->when($request->input('users') === 'with', fn ($q) => $q->has('users'))
            ->when($request->input('users') === 'without', fn ($q) => $q->doesntHave('users'))
            // Filtre coupures
            ->when($request->input('outages') === 'with', fn ($q) => $q->has('powerOutages'))
            ->when($request->input('outages') === 'without', fn ($q) => $q->doesntHave('powerOutages'))
            // Filtre position GPS
            ->when($request->input('map') === 'with', fn ($q) => $q->whereNotNull('latitude')->whereNotNull('longitude'))
            ->when($request->input('map') === 'without', fn ($q) => $q->where(
                fn ($qq) => $qq->whereNull('latitude')->orWhereNull('longitude')
            ))
            ->orderBy($sort, $dir)
            ->paginate(5)
            ->withQueryString(); // garde recherche/filtres/tri quand on change de page

        $cities = Neighborhood::query()
            ->whereNotNull('city')
            ->select('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return view('neighborhoods.index', compact('neighborhoods', 'cities'));
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