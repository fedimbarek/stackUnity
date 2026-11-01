<?php

namespace Tests\Feature\Weather;

use App\Models\Neighborhood;
use App\Models\OutageRisk;
use App\Models\WeatherForecast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class WeatherForecastControllerTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function forecast(array $attributes = []): WeatherForecast
    {
        return WeatherForecast::factory()->create($attributes + [
            'neighborhood_id' => Neighborhood::factory()->create()->id,
        ]);
    }

    private function payload(Neighborhood $n, array $override = []): array
    {
        return array_merge([
            'neighborhood_id' => $n->id, 'date' => '2026-08-01',
            'temp_max' => 41, 'temp_min' => 29, 'level' => 'canicule',
        ], $override);
    }

    public function test_access_is_reserved_to_admin_and_gestionnaire(): void
    {
        $this->get(route('admin.weather.forecasts.index'))->assertRedirect(route('login'));
        $this->actingAs($this->resident())->get(route('admin.weather.forecasts.index'))->assertForbidden();
        $this->actingAs($this->gestionnaire())->get(route('admin.weather.forecasts.index'))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.weather.forecasts.index'))->assertOk();
    }

    public function test_index_is_paginated_and_counts_the_risks(): void
    {
        $n = Neighborhood::factory()->create();
        foreach (range(1, 16) as $i) {
            $f = WeatherForecast::factory()->create(['neighborhood_id' => $n->id, 'date' => today()->addDays($i)->toDateString()]);
            if ($i === 16) {
                OutageRisk::factory()->count(2)->for($f, 'forecast')->create();
            }
        }

        $this->actingAs($this->admin())->get(route('admin.weather.forecasts.index'))
            ->assertOk()
            ->assertViewIs('admin.weather.forecasts.index')
            ->assertViewHas('forecasts', function ($forecasts) {
                return $forecasts->count() === 15
                    && $forecasts->total() === 16
                    && $forecasts->first()->outage_risks_count === 2; // tri date décroissante
            });
    }

    public function test_show_displays_the_forecast_with_its_relations(): void
    {
        $f = $this->forecast();
        OutageRisk::factory()->for($f, 'forecast')->create();

        $this->actingAs($this->admin())->get(route('admin.weather.forecasts.show', $f))
            ->assertOk()
            ->assertViewIs('admin.weather.forecasts.show')
            ->assertViewHas('forecast', fn ($x) => $x->relationLoaded('neighborhood') && $x->outageRisks->count() === 1);
    }

    public function test_create_and_edit_forms_receive_the_neighborhoods(): void
    {
        $f = $this->forecast();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.weather.forecasts.create'))
            ->assertOk()
            ->assertViewIs('admin.weather.forecasts.create')
            ->assertViewHas('forecast', fn ($x) => ! $x->exists)
            ->assertViewHas('neighborhoods', fn ($n) => $n->count() === 1);

        $this->actingAs($admin)->get(route('admin.weather.forecasts.edit', $f))
            ->assertOk()
            ->assertViewIs('admin.weather.forecasts.edit')
            ->assertViewHas('forecast', fn ($x) => $x->is($f));
    }

    public function test_store_creates_a_forecast(): void
    {
        $n = Neighborhood::factory()->create();

        $this->actingAs($this->gestionnaire())
            ->post(route('admin.weather.forecasts.store'), $this->payload($n))
            ->assertRedirect(route('admin.weather.forecasts.index'))
            ->assertSessionHas('status', 'Prévision ajoutée.');

        $this->assertSame(1, WeatherForecast::where('neighborhood_id', $n->id)->where('level', 'canicule')->count());
    }

    public function test_store_validates_the_fields(): void
    {
        $n = Neighborhood::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.weather.forecasts.store'), [])
            ->assertSessionHasErrors(['neighborhood_id', 'date', 'temp_max', 'temp_min', 'level']);

        $this->actingAs($this->admin())
            ->post(route('admin.weather.forecasts.store'), $this->payload($n, ['temp_max' => 20, 'temp_min' => 30]))
            ->assertSessionHasErrors('temp_min');

        $this->assertSame(0, WeatherForecast::count());
    }

    public function test_store_rejects_a_second_forecast_for_the_same_day_and_neighborhood(): void
    {
        $n = Neighborhood::factory()->create();
        WeatherForecast::factory()->create(['neighborhood_id' => $n->id, 'date' => '2026-08-01']);

        $this->actingAs($this->admin())
            ->post(route('admin.weather.forecasts.store'), $this->payload($n))
            ->assertSessionHasErrors('date');

        $this->assertSame(1, WeatherForecast::count());
    }

    public function test_update_can_keep_its_own_date(): void
    {
        $n = Neighborhood::factory()->create();
        $f = WeatherForecast::factory()->create(['neighborhood_id' => $n->id, 'date' => '2026-08-01']);

        $this->actingAs($this->admin())
            ->put(route('admin.weather.forecasts.update', $f), $this->payload($n, ['temp_max' => 37, 'temp_min' => 26, 'level' => 'warning']))
            ->assertRedirect(route('admin.weather.forecasts.show', $f))
            ->assertSessionHas('status', 'Prévision modifiée.');

        $f->refresh();
        $this->assertSame(37.0, $f->temp_max);
        $this->assertSame('warning', $f->level);
    }

    public function test_update_is_rejected_when_it_collides_with_another_forecast(): void
    {
        $n = Neighborhood::factory()->create();
        WeatherForecast::factory()->create(['neighborhood_id' => $n->id, 'date' => '2026-08-02']);
        $f = WeatherForecast::factory()->create(['neighborhood_id' => $n->id, 'date' => '2026-08-01']);

        $this->actingAs($this->admin())
            ->put(route('admin.weather.forecasts.update', $f), $this->payload($n, ['date' => '2026-08-02']))
            ->assertSessionHasErrors('date');
    }

    public function test_destroy_deletes_the_forecast(): void
    {
        $f = $this->forecast();

        $this->actingAs($this->admin())->delete(route('admin.weather.forecasts.destroy', $f))
            ->assertRedirect(route('admin.weather.forecasts.index'))
            ->assertSessionHas('status', 'Prévision supprimée.');

        $this->assertModelMissing($f);
    }

    public function test_resident_cannot_write(): void
    {
        $f = $this->forecast();

        $this->actingAs($this->resident())->delete(route('admin.weather.forecasts.destroy', $f))->assertForbidden();
        $this->assertModelExists($f);
    }
}
