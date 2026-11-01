<?php

namespace Tests\Feature\Weather;

use App\Jobs\SendNotificationJob;
use App\Models\Neighborhood;
use App\Models\WeatherAlert;
use App\Models\WeatherForecast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class WeatherAlertControllerTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function alert(?Neighborhood $n = null, array $attributes = []): WeatherAlert
    {
        return WeatherAlert::factory()->create($attributes + [
            'neighborhood_id' => ($n ?? Neighborhood::factory()->create())->id,
        ]);
    }

    private function payload(Neighborhood $n, array $override = []): array
    {
        return array_merge([
            'neighborhood_id' => $n->id, 'level' => 'canicule',
            'message' => 'Canicule annoncée : restez hydratés.',
        ], $override);
    }

    public function test_access_is_reserved_to_admin_and_gestionnaire(): void
    {
        $this->get(route('admin.weather.alerts.index'))->assertRedirect(route('login'));
        $this->actingAs($this->resident())->get(route('admin.weather.alerts.index'))->assertForbidden();
        $this->actingAs($this->gestionnaire())->get(route('admin.weather.alerts.index'))->assertOk();
        $this->actingAs($this->admin())->get(route('admin.weather.alerts.index'))->assertOk();
    }

    public function test_index_is_paginated(): void
    {
        $n = Neighborhood::factory()->create();
        WeatherAlert::factory()->count(16)->create(['neighborhood_id' => $n->id]);

        $this->actingAs($this->admin())->get(route('admin.weather.alerts.index'))
            ->assertOk()
            ->assertViewIs('admin.weather.alerts.index')
            ->assertViewHas('alerts', fn ($a) => $a->count() === 15 && $a->total() === 16);
    }

    public function test_show_loads_the_relations(): void
    {
        $alert = $this->alert();

        $this->actingAs($this->admin())->get(route('admin.weather.alerts.show', $alert))
            ->assertOk()
            ->assertViewIs('admin.weather.alerts.show')
            ->assertViewHas('alert', fn ($a) => $a->relationLoaded('neighborhood') && $a->relationLoaded('creator'));
    }

    public function test_create_and_edit_forms_receive_the_neighborhoods(): void
    {
        $alert = $this->alert();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.weather.alerts.create'))
            ->assertOk()
            ->assertViewIs('admin.weather.alerts.create')
            ->assertViewHas('alert', fn ($a) => ! $a->exists)
            ->assertViewHas('neighborhoods', fn ($n) => $n->count() === 1);

        $this->actingAs($admin)->get(route('admin.weather.alerts.edit', $alert))
            ->assertOk()
            ->assertViewIs('admin.weather.alerts.edit')
            ->assertViewHas('alert', fn ($a) => $a->is($alert));
    }

    public function test_store_creates_a_manual_alert_and_notifies_the_neighborhood(): void
    {
        Queue::fake();
        $admin = $this->admin();
        $n = Neighborhood::factory()->create();

        $this->actingAs($admin)->post(route('admin.weather.alerts.store'), $this->payload($n))
            ->assertRedirect(route('admin.weather.alerts.index'))
            ->assertSessionHas('status');

        $alert = WeatherAlert::firstOrFail();
        $this->assertSame('manual', $alert->source);
        $this->assertSame($admin->id, $alert->created_by);
        $this->assertSame($n->id, $alert->neighborhood_id);
        Queue::assertPushed(SendNotificationJob::class, fn ($job) => $job->alert->is($alert));
    }

    public function test_store_validates_the_fields(): void
    {
        Queue::fake();
        $n = Neighborhood::factory()->create();

        $this->actingAs($this->admin())->post(route('admin.weather.alerts.store'), [])
            ->assertSessionHasErrors(['neighborhood_id', 'level', 'message']);

        $this->actingAs($this->admin())
            ->post(route('admin.weather.alerts.store'), $this->payload($n, ['level' => 'normal', 'message' => 'court']))
            ->assertSessionHasErrors(['level', 'message']);

        $this->assertSame(0, WeatherAlert::count());
        Queue::assertNothingPushed();
    }

    public function test_update_changes_a_manual_alert_including_its_neighborhood_without_notifying(): void
    {
        Queue::fake();
        $alert = $this->alert(null, ['source' => 'manual', 'level' => 'warning']);
        $other = Neighborhood::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.weather.alerts.update', $alert), $this->payload($other, ['message' => 'Message corrigé pour les habitants.']))
            ->assertRedirect(route('admin.weather.alerts.show', $alert))
            ->assertSessionHas('status');

        $alert->refresh();
        $this->assertSame($other->id, $alert->neighborhood_id);
        $this->assertSame('canicule', $alert->level);
        $this->assertSame('Message corrigé pour les habitants.', $alert->message);
        Queue::assertNothingPushed();
    }

    public function test_update_keeps_the_neighborhood_of_an_automatic_alert(): void
    {
        $n = Neighborhood::factory()->create();
        $forecast = WeatherForecast::factory()->create(['neighborhood_id' => $n->id]);
        $alert = WeatherAlert::factory()->auto()->create([
            'neighborhood_id' => $n->id, 'weather_forecast_id' => $forecast->id,
        ]);
        $other = Neighborhood::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.weather.alerts.update', $alert), $this->payload($other, ['message' => 'Nouveau message pour cette alerte.']))
            ->assertRedirect(route('admin.weather.alerts.show', $alert));

        $alert->refresh();
        $this->assertSame($n->id, $alert->neighborhood_id);
        $this->assertSame('Nouveau message pour cette alerte.', $alert->message);
    }

    public function test_destroy_deletes_the_alert(): void
    {
        $alert = $this->alert();

        $this->actingAs($this->gestionnaire())->delete(route('admin.weather.alerts.destroy', $alert))
            ->assertRedirect(route('admin.weather.alerts.index'))
            ->assertSessionHas('status', 'Alerte supprimée.');

        $this->assertModelMissing($alert);
    }

    public function test_resident_cannot_write(): void
    {
        $alert = $this->alert();

        $this->actingAs($this->resident())->post(route('admin.weather.alerts.store'), $this->payload($alert->neighborhood))->assertForbidden();
        $this->actingAs($this->resident())->delete(route('admin.weather.alerts.destroy', $alert))->assertForbidden();
        $this->assertModelExists($alert);
    }
}
