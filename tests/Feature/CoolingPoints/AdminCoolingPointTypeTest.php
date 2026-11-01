<?php

namespace Tests\Feature\CoolingPoints;

use App\Models\CoolingPoint;
use App\Models\CoolingPointType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class AdminCoolingPointTypeTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Espace Climatisé',
            'icon' => 'fa-snowflake',
            'color' => '#36b9cc',
            'description' => 'Centres commerciaux, bibliothèques...',
        ], $override);
    }

    public function test_only_an_admin_can_access_the_types(): void
    {
        $this->get(route('admin.cooling-point-types.index'))->assertRedirect(route('login'));
        $this->actingAs($this->resident())->get(route('admin.cooling-point-types.index'))->assertForbidden();
        $this->actingAs($this->gestionnaire())->post(route('admin.cooling-point-types.store'), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('cooling_point_types', 0);
    }

    public function test_index_lists_the_types_with_their_points_count(): void
    {
        $type = CoolingPointType::factory()->create();
        CoolingPoint::factory()->count(3)->create(['cooling_point_type_id' => $type->id]);

        $this->actingAs($this->admin())
            ->get(route('admin.cooling-point-types.index'))
            ->assertOk()
            ->assertViewHas('types', fn ($types) => $types->first()->cooling_points_count === 3);
    }

    public function test_create_edit_and_show_pages_are_displayed(): void
    {
        $type = CoolingPointType::factory()->create(['name' => 'Fontaine publique']);
        CoolingPoint::factory()->create(['cooling_point_type_id' => $type->id, 'name' => 'Fontaine du Lac']);
        $this->actingAs($this->admin());

        $this->get(route('admin.cooling-point-types.create'))->assertOk();
        $this->get(route('admin.cooling-point-types.edit', $type))->assertOk()->assertSee('Fontaine publique');
        $this->get(route('admin.cooling-point-types.show', $type))
            ->assertOk()
            ->assertSee('Fontaine du Lac')
            ->assertViewHas('coolingPointType', fn ($t) => $t->relationLoaded('coolingPoints'));
    }

    public function test_store_creates_the_type_with_a_slug(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.cooling-point-types.store'), $this->payload())
            ->assertRedirect(route('admin.cooling-point-types.index'))
            ->assertSessionHas('success', 'Type créé avec succès.');

        $this->assertDatabaseHas('cooling_point_types', ['name' => 'Espace Climatisé', 'slug' => 'espace-climatise']);
    }

    public function test_store_validates_required_fields_and_unique_name(): void
    {
        CoolingPointType::factory()->create(['name' => 'Parc']);
        $this->actingAs($this->admin());

        $this->post(route('admin.cooling-point-types.store'), [])
            ->assertSessionHasErrors(['name' => 'Le nom du type est obligatoire.', 'icon', 'color']);

        $this->post(route('admin.cooling-point-types.store'), $this->payload(['name' => 'Parc']))
            ->assertSessionHasErrors(['name' => 'Ce nom existe déjà.']);

        $this->post(route('admin.cooling-point-types.store'), $this->payload(['description' => str_repeat('x', 501)]))
            ->assertSessionHasErrors('description');
    }

    public function test_update_changes_the_type_and_regenerates_the_slug(): void
    {
        $type = CoolingPointType::factory()->create(['name' => 'Ancien', 'slug' => 'ancien']);

        $this->actingAs($this->admin())
            ->put(route('admin.cooling-point-types.update', $type), $this->payload(['name' => 'Bibliothèque']))
            ->assertRedirect(route('admin.cooling-point-types.index'))
            ->assertSessionHas('success', 'Type modifié.');

        $type->refresh();
        $this->assertSame('Bibliothèque', $type->name);
        $this->assertSame('bibliotheque', $type->slug);
    }

    public function test_update_can_keep_the_same_name_but_not_take_another_types_name(): void
    {
        $a = CoolingPointType::factory()->create(['name' => 'Alpha']);
        CoolingPointType::factory()->create(['name' => 'Beta']);
        $this->actingAs($this->admin());

        $this->put(route('admin.cooling-point-types.update', $a), $this->payload(['name' => 'Alpha']))
            ->assertSessionHasNoErrors();

        $this->put(route('admin.cooling-point-types.update', $a), $this->payload(['name' => 'Beta']))
            ->assertSessionHasErrors('name');
    }

    public function test_destroy_deletes_the_type(): void
    {
        $type = CoolingPointType::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.cooling-point-types.destroy', $type))
            ->assertRedirect(route('admin.cooling-point-types.index'))
            ->assertSessionHas('success', 'Type supprimé.');

        $this->assertModelMissing($type);
    }

    public function test_model_generates_a_slug_when_none_is_given(): void
    {
        $type = CoolingPointType::create(['name' => 'Jardin Public', 'icon' => 'fa-tree', 'color' => '#1cc88a']);

        $this->assertSame('jardin-public', $type->slug);
        $this->assertTrue($type->coolingPoints()->doesntExist());
    }
}
