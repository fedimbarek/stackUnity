<?php

namespace Tests\Feature\Weather;

use App\Models\Neighborhood;
use App\Models\OutageRisk;
use App\Models\WeatherForecast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class OutageRiskControllerTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function forecast(): WeatherForecast
    {
        return WeatherForecast::factory()->create(['neighborhood_id' => Neighborhood::factory()->create()->id]);
    }

    private function payload(?WeatherForecast $forecast = null, array $override = []): array
    {
        return array_merge([
            'weather_forecast_id' => ($forecast ?? $this->forecast())->id,
            'risk_level' => 'moyen',
            'description' => 'Consommation en hausse : coupures possibles.',
        ], $override);
    }

    public function test_access_is_reserved_to_admin_and_gestionnaire(): void
    {
        $risk = OutageRisk::factory()->for($this->forecast(), 'forecast')->create();

        $this->get(route('admin.weather.risks.index'))->assertRedirect(route('login'));
        $this->actingAs($this->resident())->get(route('admin.weather.risks.index'))->assertForbidden();
        $this->actingAs($this->resident())->get(route('admin.weather.risks.show', $risk))->assertForbidden();
        $this->actingAs($this->gestionnaire())->get(route('admin.weather.risks.index'))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.weather.risks.index'))->assertOk();
    }

    public function test_index_is_paginated(): void
    {
        $forecast = $this->forecast();
        OutageRisk::factory()->count(17)->for($forecast, 'forecast')->create();

        $this->actingAs($this->admin())->get(route('admin.weather.risks.index'))
            ->assertOk()
            ->assertViewIs('admin.weather.risks.index')
            ->assertViewHas('risks', fn ($risks) => $risks->count() === 15 && $risks->total() === 17);
    }

    public function test_show_displays_the_risk_with_its_forecast(): void
    {
        $risk = OutageRisk::factory()->for($this->forecast(), 'forecast')->create();

        $this->actingAs($this->admin())->get(route('admin.weather.risks.show', $risk))
            ->assertOk()
            ->assertViewIs('admin.weather.risks.show')
            ->assertViewHas('risk', fn ($r) => $r->relationLoaded('forecast') && $r->forecast->relationLoaded('neighborhood'));
    }

    public function test_create_and_edit_forms_receive_the_forecasts(): void
    {
        $forecast = $this->forecast();
        $risk = OutageRisk::factory()->for($forecast, 'forecast')->create();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.weather.risks.create'))
            ->assertOk()
            ->assertViewIs('admin.weather.risks.create')
            ->assertViewHas('risk', fn ($r) => ! $r->exists)
            ->assertViewHas('forecasts', fn ($f) => $f->count() === 1);

        $this->actingAs($admin)->get(route('admin.weather.risks.edit', $risk))
            ->assertOk()
            ->assertViewIs('admin.weather.risks.edit')
            ->assertViewHas('risk', fn ($r) => $r->is($risk))
            ->assertViewHas('forecasts', fn ($f) => $f->count() === 1);
    }

    public function test_store_creates_a_risk(): void
    {
        $this->actingAs($this->gestionnaire())
            ->post(route('admin.weather.risks.store'), $this->payload())
            ->assertRedirect(route('admin.weather.risks.index'))
            ->assertSessionHas('status', 'Risque ajouté.');

        $this->assertDatabaseHas('outage_risks', ['risk_level' => 'moyen']);
    }

    public function test_store_validates_the_fields(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.weather.risks.store'), [])
            ->assertSessionHasErrors(['weather_forecast_id', 'risk_level', 'description']);

        $this->actingAs($this->admin())
            ->post(route('admin.weather.risks.store'), $this->payload(null, ['risk_level' => 'extreme', 'description' => 'court']))
            ->assertSessionHasErrors(['risk_level', 'description']);

        $this->assertSame(0, OutageRisk::count());
    }

    public function test_update_modifies_the_risk(): void
    {
        $risk = OutageRisk::factory()->for($this->forecast(), 'forecast')->create(['risk_level' => 'faible']);

        $this->actingAs($this->admin())
            ->put(route('admin.weather.risks.update', $risk), $this->payload($risk->forecast, ['risk_level' => 'eleve']))
            ->assertRedirect(route('admin.weather.risks.show', $risk))
            ->assertSessionHas('status', 'Risque modifié.');

        $this->assertSame('eleve', $risk->fresh()->risk_level);
    }

    public function test_update_is_rejected_with_invalid_data(): void
    {
        $risk = OutageRisk::factory()->for($this->forecast(), 'forecast')->create(['risk_level' => 'faible']);

        $this->actingAs($this->admin())
            ->put(route('admin.weather.risks.update', $risk), ['risk_level' => 'nope'])
            ->assertSessionHasErrors(['weather_forecast_id', 'risk_level', 'description']);

        $this->assertSame('faible', $risk->fresh()->risk_level);
    }

    public function test_destroy_deletes_the_risk(): void
    {
        $risk = OutageRisk::factory()->for($this->forecast(), 'forecast')->create();

        $this->actingAs($this->admin())->delete(route('admin.weather.risks.destroy', $risk))
            ->assertRedirect(route('admin.weather.risks.index'))
            ->assertSessionHas('status', 'Risque supprimé.');

        $this->assertModelMissing($risk);
    }

    public function test_resident_cannot_write(): void
    {
        $risk = OutageRisk::factory()->for($this->forecast(), 'forecast')->create();
        $resident = $this->resident();

        $this->actingAs($resident)->post(route('admin.weather.risks.store'), $this->payload())->assertForbidden();
        $this->actingAs($resident)->delete(route('admin.weather.risks.destroy', $risk))->assertForbidden();
        $this->assertModelExists($risk);
    }
}
