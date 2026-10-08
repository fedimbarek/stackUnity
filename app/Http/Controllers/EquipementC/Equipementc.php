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

    public function frontIndex(): View
    {
        $equipements = $this->service->getAll();

        return view('Equipement.FrontEquipement', compact('equipements'));
    }

    public function create(): View
    {
        return view('Equipement.CreateEquipement');
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
        return view('Equipement.EditEquipement', compact('equipement'));
    }

    public function update(EquipementRequest $request, Equipement $equipement): RedirectResponse
    {
        $data = $request->validated();

        $previousImage = $equipement->image;
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('equipements', 'public');
        }

        $this->service->update($equipement, $data);

        if ($request->hasFile('image') && $previousImage) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()
            ->route('admin.equipements.index')
            ->with('success', 'Équipement modifié avec succès.');
    }

    public function destroy(Equipement $equipement): RedirectResponse
    {
        $image = $equipement->image;
        $deleted = $this->service->delete($equipement);

        if ($deleted && $image) {
            Storage::disk('public')->delete($image);
        }

        return redirect()
            ->route('admin.equipements.index')
            ->with('success', 'Équipement supprimé avec succès.');
    }
}