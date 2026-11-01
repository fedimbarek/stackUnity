<?php

namespace Tests\Feature\Api;

use App\Jobs\SendNotificationJob;
use App\Models\AlertThreshold;
use App\Models\Neighborhood;
use App\Models\WeatherAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class AlertApiTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    public function test_guest_gets_a_json_401(): void
    {
        $this->getJson(route('api.alerts.index'))->assertUnauthorized();
        $this->postJson(route('api.alerts.store'))->assertUnauthorized();
    }

    public function test_a_resident_only_sees_the_alerts_of_his_neighborhood(): void
    {
        $mine = Neighborhood::factory()->create(['name' => 'Menzah']);
        $other = Neighborhood::factory()->create();
        WeatherAlert::factory()->count(2)->create(['neighborhood_id' => $mine->id]);
        WeatherAlert::factory()->count(3)->create(['neighborhood_id' => $other->id]);

        $this->actingAs($this->resident($mine))->getJson(route('api.alerts.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.neighborhood', 'Menzah')
            ->assertJsonStructure(['data' => [['id', 'neighborhood', 'level', 'source', 'message', 'created_at']]]);
    }

    public function test_a_resident_without_neighborhood_sees_nothing(): void
    {
        WeatherAlert::factory()->create(['neighborhood_id' => Neighborhood::factory()->create()->id]);

        $this->actingAs($this->resident())->getJson(route('api.alerts.index'))
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_and_gestionnaire_see_everything_and_can_filter(): void
    {
        $a = Neighborhood::factory()->create();
        $b = Neighborhood::factory()->create();
        WeatherAlert::factory()->count(2)->create(['neighborhood_id' => $a->id]);
        WeatherAlert::factory()->count(3)->create(['neighborhood_id' => $b->id]);

        $this->actingAs($this->admin())->getJson(route('api.alerts.index'))->assertJsonCount(5, 'data');
        $this->actingAs($this->gestionnaire())->getJson(route('api.alerts.index', ['neighborhood_id' => $b->id]))
            ->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_index_validates_the_neighborhood_filter(): void
    {
        $this->actingAs($this->admin())->getJson(route('api.alerts.index', ['neighborhood_id' => 999]))
            ->assertStatus(422)->assertJsonValidationErrors('neighborhood_id');
    }

    public function test_admin_can_create_an_alert_which_notifies_the_neighborhood(): void
    {
        Queue::fake();
        $admin = $this->admin();
        $n = Neighborhood::factory()->create(['name' => 'Menzah']);

        $this->actingAs($admin)->postJson(route('api.alerts.store'), [
            'neighborhood_id' => $n->id, 'level' => 'canicule', 'message' => 'Canicule demain : restez au frais.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.neighborhood', 'Menzah')
            ->assertJsonPath('data.source', 'manual')
            ->assertJsonPath('data.level', 'canicule');

        $alert = WeatherAlert::firstOrFail();
        $this->assertSame($admin->id, $alert->created_by);
        Queue::assertPushed(SendNotificationJob::class, fn ($job) => $job->alert->is($alert));
    }

    public function test_store_validates_the_payload_and_is_reserved_to_admins(): void
    {
        Queue::fake();

        $this->actingAs($this->admin())->postJson(route('api.alerts.store'), [])
            ->assertStatus(422)->assertJsonValidationErrors(['neighborhood_id', 'level', 'message']);

        $n = Neighborhood::factory()->create();
        $payload = ['neighborhood_id' => $n->id, 'level' => 'warning', 'message' => 'Forte chaleur annoncée.'];
        $this->actingAs($this->gestionnaire())->postJson(route('api.alerts.store'), $payload)->assertForbidden();
        $this->actingAs($this->resident())->postJson(route('api.alerts.store'), $payload)->assertForbidden();

        $this->assertSame(0, WeatherAlert::count());
        Queue::assertNothingPushed();
    }

    public function test_admin_can_update_the_thresholds(): void
    {
        AlertThreshold::create(['level' => 'warning', 'temp_max' => 30]);

        $this->actingAs($this->admin())->putJson(route('api.alerts.thresholds'), ['warning' => 34, 'canicule' => 39])
            ->assertOk()
            ->assertJsonPath('message', 'Seuils enregistrés.')
            ->assertJsonPath('data.warning', 34)
            ->assertJsonPath('data.canicule', 39);

        $this->assertSame(2, AlertThreshold::count());
    }

    public function test_thresholds_are_validated_and_reserved_to_admins(): void
    {
        $this->actingAs($this->admin())->putJson(route('api.alerts.thresholds'), ['warning' => 40, 'canicule' => 38])
            ->assertStatus(422)->assertJsonValidationErrors('canicule');

        $this->actingAs($this->gestionnaire())
            ->putJson(route('api.alerts.thresholds'), ['warning' => 34, 'canicule' => 39])->assertForbidden();

        $this->assertSame(0, AlertThreshold::count());
    }
}
