<?php

namespace Tests\Feature\Auth;

use App\Models\Neighborhood;
use App\Models\User;
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
        Role::findOrCreate('resident', 'web');

        $quartier = Neighborhood::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'neighborhood_id' => $quartier->id,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'neighborhood_id' => $quartier->id,
        ]);

        $user = User::where(
            'email',
            'test@example.com'
        )->firstOrFail();

        $this->assertTrue($user->hasRole('resident'));
        $this->assertAuthenticatedAs($user);

        $response->assertRedirect(route('dashboard'));
    }
}
