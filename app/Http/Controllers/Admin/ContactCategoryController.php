<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactCategoryRequest;
use App\Models\ContactCategory;

/** BackOffice : gestion des catégories de contacts. */
class ContactCategoryController extends Controller
{
    public function index()
    {
        $categories = ContactCategory::withCount('contacts')->orderBy('name')->paginate(10);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(ContactCategoryRequest $request)
    {
        ContactCategory::create($request->validated());

        return redirect()->route('admin.contact-categories.index')
            ->with('success', 'Catégorie ajoutée.');
    }

    public function edit(ContactCategory $contact_category)
    {
        return view('admin.categories.edit', ['category' => $contact_category]);
    }

    public function update(ContactCategoryRequest $request, ContactCategory $contact_category)
    {
        $contact_category->update($request->validated());

        return redirect()->route('admin.contact-categories.index')
            ->with('success', 'Catégorie modifiée.');
    }

    public function destroy(ContactCategory $contact_category)
    {
        if ($contact_category->contacts()->exists()) {
            return back()->with('error', 'Impossible : cette catégorie contient encore des contacts.');
        }

        $contact_category->delete();

        return redirect()->route('admin.contact-categories.index')
            ->with('success', 'Catégorie supprimée.');
    }
}
