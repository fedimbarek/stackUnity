<?php

namespace Tests\Feature\Auth;

use App\Models\Neighborhood;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_users_can_register(): void
    {
        // Initialiser les rôles du projet
        $this->seed(RoleSeeder::class);

        // Garantir la présence du rôle resident
        Role::findOrCreate('resident', 'web');

        // Créer un quartier pour l'utilisateur
        $neighborhood = Neighborhood::factory()->create();

        // Envoyer le formulaire d'inscription
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'neighborhood_id' => $neighborhood->id,
        ]);

        // Vérifier l'absence d'erreurs de validation
        $response->assertSessionHasNoErrors();

        // Vérifier l'enregistrement en base
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'neighborhood_id' => $neighborhood->id,
        ]);

        // Récupérer l'utilisateur créé
        $user = User::where('email', 'test@example.com')
            ->firstOrFail();

        // Vérifier le rôle attribué
        $this->assertTrue($user->hasRole('resident'));

        // Vérifier que l'utilisateur est connecté
        $this->assertAuthenticatedAs($user);

        // Vérifier la redirection vers le dashboard
        $response->assertRedirect(route('dashboard'));
    }
}
