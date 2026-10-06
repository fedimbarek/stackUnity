<?php

namespace App\Http\Controllers;

use App\Models\Equipement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function store(Request $request, Equipement $equipement): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'numero' => ['required', 'string', 'max:30'],
            'date_debut' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'date_fin' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_debut'],
        ]);

        $equipement->reservations()->create($validated);

        return redirect()
            ->route('front.equipements')
            ->with('success', 'Votre demande de réservation a été enregistrée.');
    }
}
