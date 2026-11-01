<?php

namespace Tests\Feature;

use App\Models\Equipement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_equipement_shows_reservation_form(): void
    {
        $equipement = Equipement::factory()->create();

        $this->get(route('front.equipements'))
            ->assertOk()
            ->assertSee(route('front.equipements.reservations.store', $equipement), false)
            ->assertSee('Envoyer la demande');
    }

    public function test_public_reservation_request_is_created(): void
    {
        $equipement = Equipement::factory()->create();

        $response = $this->post(route('front.equipements.reservations.store', $equipement), [
            'nom' => 'Dupont',
            'prenom' => 'Alice',
            'email' => 'alice@example.com',
            'numero' => '12345678',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(2)->toDateString(),
        ]);

        $response
            ->assertRedirect(route('front.equipements'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reservations', [
            'equipement_id' => $equipement->id,
            'email' => 'alice@example.com',
            'confirmee_at' => null,
        ]);
    }

    public function test_admin_can_list_confirm_and_delete_reservations(): void
    {
        $equipement = Equipement::factory()->create();
        $reservation = $equipement->reservations()->create([
            'nom' => 'Dupont',
            'prenom' => 'Alice',
            'email' => 'alice@example.com',
            'numero' => '12345678',
            'date_debut' => now()->toDateString(),
            'date_fin' => now()->addDays(2)->toDateString(),
        ]);

        $admin = $this->createAdminUser();
        $this->actingAs($admin)
            ->get(route('admin.equipements.reservations.index', $equipement))
            ->assertOk()
            // ->assertSee('Alice Dupont');
             ->assertSee('Dupont')
            ->assertSee('Alice')
            ->assertSee('alice@example.com');

        $this->patch(route('admin.equipements.reservations.confirm', [$equipement, $reservation]))
            ->assertRedirect(route('admin.equipements.reservations.index', $equipement));

        $this->assertNotNull($reservation->fresh()->confirmee_at);
        $this->assertSame('reserve', $equipement->fresh()->etat);

        $this->delete(route('admin.equipements.reservations.destroy', [$equipement, $reservation]))
            ->assertRedirect(route('admin.equipements.reservations.index', $equipement));

        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
        $this->assertSame('disponible', $equipement->fresh()->etat);
    }

    private function createAdminUser(): User
    {
        Role::findOrCreate('admin', 'web');

        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }
}
