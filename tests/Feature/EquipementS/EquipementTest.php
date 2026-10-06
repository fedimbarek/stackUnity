<?php

namespace Tests\Feature;

use App\Models\Equipement;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EquipementTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_equipements_page_is_displayed(): void
    {
        Equipement::factory()->create([
            'nom' => 'Tente de secours',
            'type_equipement' => 'Hébergement',
        ]);

        $response = $this->get(route('front.equipements'));

        $response
            ->assertOk()
            ->assertViewIs('Equipement.FrontEquipement')
            ->assertSee('Tente de secours')
            ->assertSee('Équipements disponibles')
            ->assertSee('Réserver')
            ->assertSee('name="date_debut"', false)
            ->assertSee('href="' . route('front.equipements') . '"', false);
    }

    public function test_reservation_can_be_created_for_an_equipement(): void
    {
        $equipement = Equipement::factory()->create();
        $dateDebut = now()->addDay()->toDateString();
        $dateFin = now()->addDays(3)->toDateString();

        $response = $this->post(route('front.equipements.reservations.store', $equipement), [
            'nom' => 'Ben Ali',
            'prenom' => 'Amira',
            'email' => 'amira@example.com',
            'numero' => '22123456',
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
        ]);

        $response
            ->assertRedirect(route('front.equipements'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reservations', [
            'equipement_id' => $equipement->id,
            'nom' => 'Ben Ali',
            'prenom' => 'Amira',
            'email' => 'amira@example.com',
            'numero' => '22123456',
        ]);

        $reservation = $equipement->reservations()->firstOrFail();

        $this->assertInstanceOf(Reservation::class, $reservation);
        $this->assertSame($equipement->id, $reservation->equipement->id);
        $this->assertSame($dateDebut, $reservation->date_debut->toDateString());
        $this->assertSame($dateFin, $reservation->date_fin->toDateString());
    }

    public function test_reservation_end_date_cannot_be_before_start_date(): void
    {
        $equipement = Equipement::factory()->create();
        $dateDebut = now()->addDays(2)->toDateString();

        $response = $this->from(route('front.equipements'))->post(
            route('front.equipements.reservations.store', $equipement),
            [
                'nom' => 'Ben Ali',
                'prenom' => 'Amira',
                'email' => 'amira@example.com',
                'numero' => '22123456',
                'date_debut' => $dateDebut,
                'date_fin' => now()->addDay()->toDateString(),
            ]
        );

        $response->assertSessionHasErrors('date_fin');
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_manager_can_view_reservations_for_selected_equipement(): void
    {
        $role = Role::findOrCreate('gestionnaire', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        $equipement = Equipement::factory()->create(['nom' => 'Tente de secours']);
        $autreEquipement = Equipement::factory()->create(['nom' => 'Groupe électrogène']);

        $equipement->reservations()->create([
            'nom' => 'Ben Ali',
            'prenom' => 'Amira',
            'email' => 'amira@example.com',
            'numero' => '22123456',
            'date_debut' => now()->addDay()->toDateString(),
            'date_fin' => now()->addDays(3)->toDateString(),
        ]);

        $autreEquipement->reservations()->create([
            'nom' => 'Autre client',
            'prenom' => 'Test',
            'email' => 'autre@example.com',
            'numero' => '22987654',
            'date_debut' => now()->addDay()->toDateString(),
            'date_fin' => now()->addDays(2)->toDateString(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('admin.equipements.reservations.index', $equipement));

        $response
            ->assertOk()
            ->assertViewIs('Equipement.Reservations')
            ->assertSee('Tente de secours')
            ->assertSee('amira@example.com')
            ->assertDontSee('autre@example.com');
    }

    public function test_equipements_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/equipements');

        $response->assertOk();
    }

    public function test_equipement_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/equipements', [
                'nom' => 'Tente 4 places',
                'type_equipement' => 'Camping',
                'date_ajout' => '2026-09-28',
                'image' => 'equipements/tente.jpg',
                'prix_louer' => 45.50,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/equipements');

        $this->assertDatabaseHas('equipements', [
            'nom' => 'Tente 4 places',
            'type_equipement' => 'Camping',
            'prix_louer' => 45.50,
        ]);
    }

    public function test_equipement_can_be_updated(): void
    {
        $user = User::factory()->create();
        $equipement = Equipement::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch("/equipements/{$equipement->id}", [
                'nom' => 'Nouveau nom',
                'type_equipement' => 'Sport',
                'date_ajout' => '2026-09-28',
                'image' => 'equipements/nouveau.jpg',
                'prix_louer' => 99.99,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/equipements');

        $equipement->refresh();

        $this->assertSame('Nouveau nom', $equipement->nom);
        $this->assertSame('Sport', $equipement->type_equipement);
        $this->assertEquals(99.99, $equipement->prix_louer);
    }

    public function test_equipement_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $equipement = Equipement::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete("/equipements/{$equipement->id}");

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/equipements');

        $this->assertNull($equipement->fresh());
    }

    public function test_nom_and_prix_louer_are_required(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/equipements/create')
            ->post('/equipements', [
                'nom' => '',
                'prix_louer' => '',
            ]);

        $response
            ->assertSessionHasErrors(['nom', 'prix_louer'])
            ->assertRedirect('/equipements/create');

        $this->assertDatabaseCount('equipements', 0);
    }
}