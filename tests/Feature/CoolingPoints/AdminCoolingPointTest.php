<?php

namespace Tests\Feature\CoolingPoints;

use App\Models\CoolingPoint;
use App\Models\CoolingPointType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class AdminCoolingPointTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function payload(CoolingPointType $type, array $override = []): array
    {
        return array_merge([
            'cooling_point_type_id' => $type->id,
            'name' => 'Parc du Belvédère',
            'latitude' => 36.8231,
            'longitude' => 10.1816,
            'address' => 'Avenue Taieb Mhiri',
            'opening_hours' => '08:00-20:00',
        ], $override);
    }

    public function test_guest_and_non_admin_cannot_access_the_admin_pages(): void
    {
        $this->get(route('admin.cooling-points.index'))->assertRedirect(route('login'));

        $this->actingAs($this->resident())->get(route('admin.cooling-points.index'))->assertForbidden();
        $this->actingAs($this->gestionnaire())->get(route('admin.cooling-points.index'))->assertForbidden();
    }

    public function test_admin_sees_the_paginated_list(): void
    {
        CoolingPoint::factory()->count(12)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.cooling-points.index'))
            ->assertOk()
            ->assertViewIs('admin.cooling-points.index')
            ->assertViewHas('points', fn ($points) => $points->count() === 10 && $points->total() === 12);
    }

    public function test_create_and_edit_forms_receive_the_types(): void
    {
        $type = CoolingPointType::factory()->create();
        $point = CoolingPoint::factory()->create(['cooling_point_type_id' => $type->id]);
        $this->actingAs($this->admin());

        $this->get(route('admin.cooling-points.create'))
            ->assertOk()
            ->assertViewHas('types', fn ($types) => $types->contains($type));

        $this->get(route('admin.cooling-points.edit', $point))
            ->assertOk()
            ->assertViewHas('coolingPoint', fn ($p) => $p->is($point));
    }

    public function test_admin_can_view_a_point(): void
    {
        $point = CoolingPoint::factory()->create(['name' => 'Fontaine Centrale']);

        $this->actingAs($this->admin())
            ->get(route('admin.cooling-points.show', $point))
            ->assertOk()
            ->assertSee('Fontaine Centrale');
    }

    public function test_admin_can_create_a_point_which_is_flagged_as_manual(): void
    {
        $type = CoolingPointType::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.cooling-points.store'), $this->payload($type))
            ->assertRedirect(route('admin.cooling-points.index'))
            ->assertSessionHas('success', 'Point créé.');

        $this->assertDatabaseHas('cooling_points', [
            'name' => 'Parc du Belvédère',
            'cooling_point_type_id' => $type->id,
            'source' => 'manual',
        ]);
    }

    public function test_store_validates_the_fields(): void
    {
        $type = CoolingPointType::factory()->create();
        $this->actingAs($this->admin());

        $this->post(route('admin.cooling-points.store'), [])
            ->assertSessionHasErrors(['cooling_point_type_id', 'name', 'latitude', 'longitude']);

        $this->post(route('admin.cooling-points.store'), $this->payload($type, ['cooling_point_type_id' => 9999]))
            ->assertSessionHasErrors(['cooling_point_type_id' => 'Type invalide.']);

        $this->post(route('admin.cooling-points.store'), $this->payload($type, ['latitude' => 91, 'longitude' => 181]))
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->post(route('admin.cooling-points.store'), $this->payload($type, ['name' => str_repeat('a', 151)]))
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('cooling_points', 0);
    }

    public function test_admin_can_update_a_point_without_changing_its_source(): void
    {
        $type = CoolingPointType::factory()->create();
        $point = CoolingPoint::factory()->create(['source' => 'osm', 'name' => 'Ancien nom']);

        $this->actingAs($this->admin())
            ->put(route('admin.cooling-points.update', $point), $this->payload($type, ['name' => 'Nouveau nom']))
            ->assertRedirect(route('admin.cooling-points.index'))
            ->assertSessionHas('success', 'Point modifié.');

        $point->refresh();
        $this->assertSame('Nouveau nom', $point->name);
        $this->assertSame($type->id, $point->cooling_point_type_id);
        $this->assertSame('osm', $point->source);
    }

    public function test_update_is_rejected_with_invalid_data(): void
    {
        $point = CoolingPoint::factory()->create(['name' => 'Intact']);

        $this->actingAs($this->admin())
            ->put(route('admin.cooling-points.update', $point), ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertSame('Intact', $point->fresh()->name);
    }

    public function test_admin_can_delete_a_point(): void
    {
        $point = CoolingPoint::factory()->create();

        $this->actingAs($this->admin())
            ->delete(route('admin.cooling-points.destroy', $point))
            ->assertRedirect(route('admin.cooling-points.index'))
            ->assertSessionHas('success', 'Point supprimé.');

        $this->assertModelMissing($point);
    }

    public function test_non_admin_cannot_write(): void
    {
        $type = CoolingPointType::factory()->create();
        $point = CoolingPoint::factory()->create();
        $this->actingAs($this->gestionnaire());

        $this->post(route('admin.cooling-points.store'), $this->payload($type))->assertForbidden();
        $this->put(route('admin.cooling-points.update', $point), $this->payload($type))->assertForbidden();
        $this->delete(route('admin.cooling-points.destroy', $point))->assertForbidden();

        $this->assertModelExists($point);
    }
}
