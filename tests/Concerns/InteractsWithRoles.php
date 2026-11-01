<?php

namespace Tests\Concerns;

use App\Models\Neighborhood;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Petits helpers pour créer des utilisateurs avec un rôle Spatie.
 */
trait InteractsWithRoles
{
    protected function makeUser(string $role = 'resident', ?Neighborhood $neighborhood = null, array $attributes = []): User
    {
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create(array_merge(
            ['neighborhood_id' => $neighborhood?->id],
            $attributes
        ));
        $user->assignRole($role);

        return $user;
    }

    protected function admin(?Neighborhood $neighborhood = null, array $attributes = []): User
    {
        return $this->makeUser('admin', $neighborhood, $attributes);
    }

    protected function gestionnaire(?Neighborhood $neighborhood = null, array $attributes = []): User
    {
        return $this->makeUser('gestionnaire', $neighborhood, $attributes);
    }

    protected function resident(?Neighborhood $neighborhood = null, array $attributes = []): User
    {
        return $this->makeUser('resident', $neighborhood, $attributes);
    }
}