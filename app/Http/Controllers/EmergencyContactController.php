<?php

namespace App\Http\Controllers;

use App\Models\ContactCategory;
use App\Models\EmergencyContact;
use App\Services\EmergencyAssistantService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/** FrontOffice : consultation de l'annuaire d'urgence. */
class EmergencyContactController extends Controller
{
    public function index(Request $request)
    {
        $categories = ContactCategory::withCount(['contacts' => fn ($q) => $q->active()])
            ->orderBy('name')
            ->get();

        $priorityContacts = EmergencyContact::with('category')
            ->active()
            ->where('is_priority', true)
            ->orderBy('name')
            ->get();

        $contacts = EmergencyContact::with('category')   // eager loading de la relation
            ->active()
            ->search($request->query('q'))
            ->ofCategory($request->query('category'))
            ->orderByDesc('is_24h')
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        return view('contacts.index', compact('categories', 'priorityContacts', 'contacts'));
    }

    public function show(EmergencyContact $contact)
    {
        abort_unless($contact->is_active, 404);

        $contact->load('category');

        $related = EmergencyContact::with('category')
            ->active()
            ->where('contact_category_id', $contact->contact_category_id)
            ->whereKeyNot($contact->id)
            ->limit(3)
            ->get();

        return view('contacts.show', compact('contact', 'related'));
    }

    // =====================================================================
    // MÉTIER AVANCÉ : afficher TOUS les contacts + export PDF
    // =====================================================================

    /** Page avec tous les contacts actifs, groupés par catégorie (sans pagination). */
    public function all()
    {
        return view('contacts.all', $this->directoryData());
    }

    /** Téléchargement de l'annuaire complet en PDF. */
    public function pdf()
    {
        $pdf = Pdf::loadView('contacts.pdf', $this->directoryData())
            ->setPaper('a4', 'portrait');

        return $pdf->download('contacts-urgence-' . now()->format('Y-m-d') . '.pdf');
    }

    /** Données communes à la page "tous les contacts" et au PDF. */
    private function directoryData(): array
    {
        $categories = ContactCategory::with(['contacts' => fn ($q) => $q->active()
                ->orderByDesc('is_priority')
                ->orderByDesc('is_24h')
                ->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->filter(fn ($category) => $category->contacts->isNotEmpty())
            ->values();

        $priorityContacts = EmergencyContact::with('category')
            ->active()
            ->where('is_priority', true)
            ->orderBy('name')
            ->get();

        return [
            'categories'       => $categories,
            'priorityContacts' => $priorityContacts,
            'total'            => $categories->sum(fn ($c) => $c->contacts->count()),
            'generatedAt'      => now(),
        ];
    }

    // =====================================================================
    // ASSISTANT IA : situation -> meilleur numéro à appeler
    // =====================================================================

    public function assistant(Request $request, EmergencyAssistantService $assistant)
    {
        $data = $request->validate([
            'situation' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'situation.required' => 'Décrivez votre situation.',
            'situation.min'      => 'Donnez un peu plus de détails (au moins 10 caractères).',
            'situation.max'      => 'Description trop longue (1000 caractères max).',
        ]);

        return response()->json($assistant->suggest($data['situation']));
    }
}
