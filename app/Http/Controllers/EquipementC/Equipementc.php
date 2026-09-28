<?php

namespace App\Http\Controllers\EquipementC;

use App\Http\Controllers\Controller;
use App\Http\Requests\EquipementRequest;
use App\Models\Equipement;
use App\Services\EquipementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class Equipementc extends Controller
{
    public function __construct(private EquipementService $service)
    {
    }

    public function index(): View
{
    $equipements = $this->service->getAll();

    return view('Equipement.StoreEquipement', compact('equipements'));
}
    public function create(): View
    {
        return view('equipements.create');
    }

    public function store(EquipementRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('equipements', 'public');
        }

        $data['date_ajout'] = $data['date_ajout'] ?? now()->toDateString();

        $this->service->create($data);

        return redirect()
            ->route('admin.equipements.index')
            ->with('success', 'Équipement ajouté avec succès.');
    }

    public function show(Equipement $equipement): View
    {
        return view('equipements.show', compact('equipement'));
    }

    public function edit(Equipement $equipement): View
    {
        return view('equipements.edit', compact('equipement'));
    }

    public function update(EquipementRequest $request, Equipement $equipement): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($equipement->image) {
                Storage::disk('public')->delete($equipement->image);
            }

            $data['image'] = $request->file('image')->store('equipements', 'public');
        }

        $this->service->update($equipement, $data);

        return redirect()
            ->route('admin.equipements.index')
            ->with('success', 'Équipement modifié avec succès.');
    }

    public function destroy(Equipement $equipement): RedirectResponse
    {
        if ($equipement->image) {
            Storage::disk('public')->delete($equipement->image);
        }

        $this->service->delete($equipement);

        return redirect()
            ->route('admin.equipements.index')
            ->with('success', 'Équipement supprimé avec succès.');
    }
}