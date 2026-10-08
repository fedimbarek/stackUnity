<?php

namespace Tests\Feature;

use App\Models\Equipement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_can_be_created_from_front_office(): void
    {
        $equipement = Equipement::factory()->create();

        $response = $this->post("/equipements/{$equipement->id}/reservations", [
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane@example.com',
            'numero' => '12345678',
            'date_debut' => now()->addDay()->toDateString(),
            'date_fin' => now()->addDays(3)->toDateString(),
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/Equipements');

        $this->assertDatabaseHas('reservations', [
            'equipement_id' => $equipement->id,
            'nom' => 'Doe',
            'email' => 'jane@example.com',
        ]);
    }

    public function test_admin_can_view_reservations_for_equipment(): void
    {
        $equipement = Equipement::factory()->create();
        $equipement->reservations()->create([
            'nom' => 'Doe',
            'prenom' => 'Jane',
            'email' => 'jane@example.com',
            'numero' => '12345678',
            'date_debut' => now()->addDay()->toDateString(),
            'date_fin' => now()->addDays(3)->toDateString(),
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->get("/admin/equipements/{$equipement->id}/reservations");

        $response->assertOk()->assertSee('Doe')->assertSee('Jane');
    }
}
