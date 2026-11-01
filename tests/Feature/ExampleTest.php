
<?php

namespace Tests\Feature;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        // Initialiser les rôles du projet
        $this->seed(RoleSeeder::class);

        // Garantir la présence des rôles nécessaires
        Role::findOrCreate('resident', 'web');
        Role::findOrCreate('gestionnaire', 'web');
        Role::findOrCreate('admin', 'web');

        // Vérifier que la page d'accueil fonctionne
        $this->get('/')->assertOk();
    }
}
