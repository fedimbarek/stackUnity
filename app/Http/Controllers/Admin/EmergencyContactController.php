<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmergencyContactRequest;
use App\Models\ContactCategory;
use App\Models\EmergencyContact;
use Illuminate\Http\Request;

/** BackOffice : CRUD complet des contacts d'urgence. */
class EmergencyContactController extends Controller
{
    // READ : liste (avec recherche + filtre + pagination)
    public function index(Request $request)
    {
        $contacts = EmergencyContact::with('category')
            ->search($request->query('q'))
            ->ofCategory($request->query('category'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $categories = ContactCategory::orderBy('name')->get();

        return view('admin.contacts.index', compact('contacts', 'categories'));
    }

    // CREATE : formulaire
    public function create()
    {
        $categories = ContactCategory::orderBy('name')->get();

        return view('admin.contacts.create', compact('categories'));
    }

    // CREATE : enregistrement
    public function store(EmergencyContactRequest $request)
    {
        EmergencyContact::create($request->validated());

        return redirect()->route('admin.contacts.index')
            ->with('success', 'Contact d\'urgence ajouté avec succès.');
    }

    // UPDATE : formulaire pré-rempli
    public function edit(EmergencyContact $contact)
    {
        $categories = ContactCategory::orderBy('name')->get();

        return view('admin.contacts.edit', compact('contact', 'categories'));
    }

    // UPDATE : enregistrement
    public function update(EmergencyContactRequest $request, EmergencyContact $contact)
    {
        $contact->update($request->validated());

        return redirect()->route('admin.contacts.index')
            ->with('success', 'Contact modifié avec succès.');
    }

    // DELETE
    public function destroy(EmergencyContact $contact)
    {
        $contact->delete();

        return redirect()->route('admin.contacts.index')
            ->with('success', 'Contact supprimé.');
    }
}
