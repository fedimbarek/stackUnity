<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        // Préparer les rôles utilisés par la page d'accueil
        Role::findOrCreate('resident', 'web');
        Role::findOrCreate('gestionnaire', 'web');
        Role::findOrCreate('admin', 'web');

        // Vérifier la page d'accueil
        $this->get('/')
            ->assertOk();
    }
}
