<?php

namespace App\Http\Controllers\EquipementC;

use App\Http\Controllers\Controller;
use App\Http\Requests\EquipementRequest;
use App\Http\Requests\EquipementTranslationsRequest;
use App\Http\Requests\RecommandationRequest;
use App\Models\Equipement;
use App\Services\EquipementAdvisorService;
use App\Services\EquipementService;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class Equipementc extends Controller
{
    public function __construct(private EquipementService $service) {}

    public function index(): View
    {
        $equipements = $this->service->getAll();

        return view('Equipement.StoreEquipement', compact('equipements'));
    }

    public function create(): View
    {
        return view('Equipement.CreateEquipement');
    }

    public function frontIndex(): View
    {
        $equipements = $this->service->getAll();

        return view('Equipement.FrontEquipement', compact('equipements'));
    }

    public function recommander(
        RecommandationRequest $request,
        EquipementAdvisorService $advisor
    ): JsonResponse {
        $validated = $request->validated();

        return response()->json($advisor->recommend(
            $validated['besoin'],
            $validated['lang'] ?? 'fr'
        ));
    }

    public function translations(
        EquipementTranslationsRequest $request,
        TranslationService $translationService
    ): JsonResponse {
        $validated = $request->validated();
        $target = $validated['lang'];
        $query = Equipement::query()->orderBy('id');

        if (isset($validated['ids'])) {
            $query->whereIn('id', $validated['ids']);
        }

        $equipements = $query->limit(50)->get(['id', 'nom', 'type_equipement']);
        $uniqueValues = $equipements
            ->flatMap(fn (Equipement $equipement) => [$equipement->nom, $equipement->type_equipement])
            ->unique()
            ->values();
        $translatedValues = [];

        foreach ($uniqueValues as $value) {
            $translatedValues[$value] = $target === 'fr'
                ? $value
                : ($translationService->translate($value, 'fr', $target) ?? $value);
        }

        $translations = [];

        foreach (['nom', 'type_equipement'] as $field) {
            foreach ($equipements as $equipement) {
                $translations[$equipement->id][$field] = $translatedValues[$equipement->{$field}];
            }
        }

        return response()->json([
            'lang' => $target,
            'items' => $translations,
        ]);
    }

    public function reservations(Equipement $equipement): View
    {
        $reservations = $equipement->reservations()
            ->latest()
            ->paginate(15);

        return view('Equipement.Reservations', compact('equipement', 'reservations'));
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

    public function translate(
        Request $request,
        Equipement $equipement,
        TranslationService $translationService
    ): JsonResponse {
        $validated = $request->validate([
            'lang' => ['sometimes', 'string', 'in:en'],
        ]);
        $target = $validated['lang'] ?? 'en';

        $nom = $translationService->translate($equipement->nom, 'fr', $target);
        $type = $translationService->translate($equipement->type_equipement, 'fr', $target);

        if ($nom === null || $type === null) {
            return response()->json([
                'success' => false,
                'message' => 'La traduction est temporairement indisponible.',
            ], 503);
        }

        return response()->json([
            'id' => $equipement->id,
            'lang' => $target,
            'success' => true,
            'nom' => $nom,
            'type_equipement' => $type,
        ]);
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