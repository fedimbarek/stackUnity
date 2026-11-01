<?php

namespace Tests\Feature\Neighborhoods;

use App\Models\Neighborhood;
use App\Models\PowerOutage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class NeighborhoodControllerTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'La Marsa',
            'city' => 'Tunis',
            'latitude' => 36.8782,
            'longitude' => 10.3247,
        ], $override);
    }

    private function names($response): array
    {
        return $response->viewData('neighborhoods')->pluck('name')->all();
    }

    public function test_guest_is_redirected_and_resident_is_forbidden(): void
    {
        $this->get(route('neighborhoods.index'))->assertRedirect(route('login'));
        $this->actingAs($this->resident())->get(route('neighborhoods.index'))->assertForbidden();
        $this->post(route('neighborhoods.store'), $this->payload())->assertForbidden();
    }

    public function test_admin_and_gestionnaire_can_open_the_pages(): void
    {
        $n = Neighborhood::factory()->create();

        foreach ([$this->admin(), $this->gestionnaire()] as $user) {
            $this->actingAs($user);
            $this->get(route('neighborhoods.index'))->assertOk()->assertViewIs('neighborhoods.index')->assertViewHas('cities');
            $this->get(route('neighborhoods.create'))->assertOk();
            $this->get(route('neighborhoods.show', $n))->assertOk()->assertViewIs('neighborhoods.show');
            $this->get(route('neighborhoods.edit', $n))->assertOk()->assertViewHas('neighborhood', fn ($v) => $v->is($n));
        }
    }

    public function test_index_search_by_name_or_city(): void
    {
        Neighborhood::factory()->create(['name' => 'Lafayette', 'city' => 'Tunis']);
        Neighborhood::factory()->create(['name' => 'Cite Ennasr', 'city' => 'Ariana']);
        $this->actingAs($this->admin());

        $this->assertSame(['Lafayette'], $this->names($this->get(route('neighborhoods.index', ['search' => 'fayet']))));
        $this->assertSame(['Cite Ennasr'], $this->names($this->get(route('neighborhoods.index', ['search' => 'Ariana']))));
    }

    public function test_index_filters_by_city(): void
    {
        Neighborhood::factory()->create(['name' => 'A Quartier', 'city' => 'Tunis']);
        Neighborhood::factory()->create(['name' => 'B Quartier', 'city' => 'Sfax']);
        $this->actingAs($this->admin());

        $this->assertSame(['B Quartier'], $this->names($this->get(route('neighborhoods.index', ['city' => 'Sfax']))));
    }

    public function test_index_filters_by_users_and_outages(): void
    {
        $withUser = Neighborhood::factory()->create(['name' => 'Avec Habitant']);
        $withOutage = Neighborhood::factory()->create(['name' => 'Avec Coupure']);
        $empty = Neighborhood::factory()->create(['name' => 'Vide']);
        $this->resident($withUser);
        PowerOutage::create(['neighborhood_id' => $withOutage->id, 'status' => 'reported', 'source' => 'user', 'started_at' => now()]);
        $admin = $this->admin(); // sans quartier
        $this->actingAs($admin);

        $this->assertSame(['Avec Habitant'], $this->names($this->get(route('neighborhoods.index', ['users' => 'with']))));
        $this->assertEqualsCanonicalizing(['Avec Coupure', 'Vide'], $this->names($this->get(route('neighborhoods.index', ['users' => 'without']))));
        $this->assertSame(['Avec Coupure'], $this->names($this->get(route('neighborhoods.index', ['outages' => 'with']))));
        $this->assertEqualsCanonicalizing(['Avec Habitant', 'Vide'], $this->names($this->get(route('neighborhoods.index', ['outages' => 'without']))));
    }

    public function test_index_filters_by_gps_position(): void
    {
        Neighborhood::factory()->create(['name' => 'Localise', 'latitude' => 36.8, 'longitude' => 10.1]);
        Neighborhood::factory()->create(['name' => 'Sans Gps', 'latitude' => null, 'longitude' => null]);
        $this->actingAs($this->admin());

        $this->assertSame(['Localise'], $this->names($this->get(route('neighborhoods.index', ['map' => 'with']))));
        $this->assertSame(['Sans Gps'], $this->names($this->get(route('neighborhoods.index', ['map' => 'without']))));
    }

    public function test_index_sorting_is_whitelisted(): void
    {
        Neighborhood::factory()->create(['name' => 'Alpha']);
        Neighborhood::factory()->create(['name' => 'Zeta']);
        $this->actingAs($this->admin());

        $this->assertSame(['Zeta', 'Alpha'], $this->names($this->get(route('neighborhoods.index', ['sort' => 'name', 'dir' => 'desc']))));
        // colonne inconnue : retombe sur le tri par nom croissant, sans erreur SQL
        $this->assertSame(['Alpha', 'Zeta'], $this->names($this->get(route('neighborhoods.index', ['sort' => 'password; drop table users']))));
        $this->get(route('neighborhoods.index', ['sort' => 'users_count']))->assertOk();
    }

    public function test_index_is_paginated_by_five(): void
    {
        Neighborhood::factory()->count(7)->create();

        $response = $this->actingAs($this->admin())->get(route('neighborhoods.index'));

        $this->assertCount(5, $response->viewData('neighborhoods'));
        $this->assertSame(7, $response->viewData('neighborhoods')->total());
    }

    public function test_store_creates_a_neighborhood(): void
    {
        $this->actingAs($this->admin())
            ->post(route('neighborhoods.store'), $this->payload(['name' => '  La Marsa  ']))
            ->assertRedirect(route('neighborhoods.index'))
            ->assertSessionHas('status', 'neighborhood-created');

        $this->assertDatabaseHas('neighborhoods', ['name' => 'La Marsa', 'city' => 'Tunis']);
    }

    public function test_store_allows_a_neighborhood_without_coordinates(): void
    {
        $this->actingAs($this->admin())
            ->post(route('neighborhoods.store'), $this->payload(['latitude' => null, 'longitude' => null]))
            ->assertRedirect(route('neighborhoods.index'));

        $this->assertDatabaseHas('neighborhoods', ['name' => 'La Marsa', 'latitude' => null]);
    }

    public function test_store_validates_the_fields(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('neighborhoods.store'), $this->payload(['name' => '', 'city' => '']))
            ->assertSessionHasErrors(['name', 'city']);
        $this->post(route('neighborhoods.store'), $this->payload(['name' => '  ab ']))
            ->assertSessionHasErrors('name');
        $this->post(route('neighborhoods.store'), $this->payload(['latitude' => 99, 'longitude' => 200]))
            ->assertSessionHasErrors(['latitude', 'longitude']);
        $this->post(route('neighborhoods.store'), $this->payload(['latitude' => 'abc']))
            ->assertSessionHasErrors('latitude');
        $this->post(route('neighborhoods.store'), $this->payload(['longitude' => null]))
            ->assertSessionHasErrors('longitude');
        $this->post(route('neighborhoods.store'), $this->payload(['latitude' => null]))
            ->assertSessionHasErrors('latitude');

        $this->assertDatabaseCount('neighborhoods', 0);
    }

    public function test_store_rejects_a_duplicate_name(): void
    {
        Neighborhood::factory()->create(['name' => 'La Marsa']);

        $this->actingAs($this->admin())->post(route('neighborhoods.store'), $this->payload())
            ->assertSessionHasErrors(['name' => 'Ce quartier existe déjà.']);
    }

    public function test_update_changes_the_neighborhood_and_can_keep_its_own_name(): void
    {
        $n = Neighborhood::factory()->create(['name' => 'Ancien', 'city' => 'Tunis']);
        Neighborhood::factory()->create(['name' => 'Autre']);
        $this->actingAs($this->admin());

        $this->put(route('neighborhoods.update', $n), $this->payload(['name' => 'Ancien', 'city' => 'Ariana']))
            ->assertRedirect(route('neighborhoods.index'))
            ->assertSessionHas('status', 'neighborhood-updated');
        $this->assertSame('Ariana', $n->fresh()->city);

        $this->put(route('neighborhoods.update', $n), $this->payload(['name' => 'Autre']))
            ->assertSessionHasErrors('name');
    }

    public function test_destroy_deletes_an_empty_neighborhood(): void
    {
        $n = Neighborhood::factory()->create();

        $this->actingAs($this->admin())->delete(route('neighborhoods.destroy', $n))
            ->assertRedirect(route('neighborhoods.index'))
            ->assertSessionHas('status', 'neighborhood-deleted');

        $this->assertModelMissing($n);
    }

    public function test_destroy_is_refused_when_users_are_attached(): void
    {
        $n = Neighborhood::factory()->create();
        $this->resident($n);

        $this->actingAs($this->admin())->delete(route('neighborhoods.destroy', $n))
            ->assertSessionHasErrors('delete');

        $this->assertModelExists($n);
    }
}
