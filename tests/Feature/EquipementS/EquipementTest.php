<?php

namespace Tests\Feature;

use App\Models\Equipement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipementTest extends TestCase
{
    use RefreshDatabase;

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