<?php

namespace App\Http\Controllers;

use App\Models\Equipement;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $created = DB::transaction(function () use ($equipement, $validated): bool {
            $lockedEquipement = Equipement::query()
                ->lockForUpdate()
                ->findOrFail($equipement->id);

            if ($lockedEquipement->etat === 'reserve') {
                return false;
            }

            $lockedEquipement->reservations()->create($validated);

            return true;
        });

        if (! $created) {
            return redirect()
                ->route('front.equipements')
                ->withErrors(['equipement' => 'Cet équipement est déjà réservé.']);
        }

        return redirect()
            ->route('front.equipements')
            ->with('success', 'Votre demande de réservation a été enregistrée.');
    }

    public function confirm(Equipement $equipement, Reservation $reservation): RedirectResponse
    {
        $result = DB::transaction(function () use ($equipement, $reservation): string {
            $lockedEquipement = Equipement::query()
                ->lockForUpdate()
                ->findOrFail($equipement->id);
            $lockedReservation = $lockedEquipement->reservations()
                ->lockForUpdate()
                ->findOrFail($reservation->id);

            if ($lockedReservation->confirmee_at) {
                return 'already_confirmed';
            }

            if ($lockedEquipement->etat === 'reserve') {
                return 'already_reserved';
            }

            $lockedReservation->forceFill(['confirmee_at' => now()])->save();
            $lockedEquipement->forceFill(['etat' => 'reserve'])->save();

            return 'confirmed';
        });

        if ($result === 'already_reserved') {
            return $this->reservationList($equipement)
                ->withErrors(['reservation' => 'Cet équipement est déjà associé à une réservation confirmée.']);
        }

        return $this->reservationList($equipement)
            ->with('success', $result === 'already_confirmed'
                ? 'Cette réservation est déjà confirmée.'
                : 'La réservation a été confirmée.');
    }

    public function destroy(Equipement $equipement, Reservation $reservation): RedirectResponse
    {
        DB::transaction(function () use ($equipement, $reservation): void {
            $lockedEquipement = Equipement::query()
                ->lockForUpdate()
                ->findOrFail($equipement->id);
            $lockedReservation = $lockedEquipement->reservations()
                ->lockForUpdate()
                ->findOrFail($reservation->id);

            $wasConfirmed = $lockedReservation->confirmee_at !== null;
            $lockedReservation->delete();

            if ($wasConfirmed) {
                $lockedEquipement->forceFill(['etat' => 'disponible'])->save();
            }
        });

        return $this->reservationList($equipement)
            ->with('success', 'La demande de réservation a été supprimée.');
    }

    private function reservationList(Equipement $equipement): RedirectResponse
    {
        return redirect()->route('admin.equipements.reservations.index', $equipement);
    }
}
