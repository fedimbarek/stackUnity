<?php

namespace App\Http\Controllers;

use App\Models\ContactCategory;
use App\Models\EmergencyContact;
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
}
