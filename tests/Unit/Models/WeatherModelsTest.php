<?php

namespace Tests\Unit\Models;

use App\Models\AlertThreshold;
use App\Models\Neighborhood;
use App\Models\OutageRisk;
use App\Models\User;
use App\Models\WeatherAlert;
use App\Models\WeatherForecast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class WeatherModelsTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    public function test_outage_risk_belongs_to_its_forecast(): void
    {
        $forecast = WeatherForecast::factory()->create(['neighborhood_id' => Neighborhood::factory()->create()->id]);
        $risk = OutageRisk::factory()->for($forecast, 'forecast')->create();

        $this->assertInstanceOf(BelongsTo::class, $risk->forecast());
        $this->assertTrue($risk->forecast->is($forecast));
        $this->assertTrue($forecast->outageRisks->first()->is($risk));
        $this->assertSame(['weather_forecast_id', 'risk_level', 'description'], $risk->getFillable());
    }

    public function test_outage_risk_factory_level_state(): void
    {
        $forecast = WeatherForecast::factory()->create(['neighborhood_id' => Neighborhood::factory()->create()->id]);

        $this->assertSame('faible', OutageRisk::factory()->for($forecast, 'forecast')->level('faible')->create()->risk_level);
        $this->assertContains(OutageRisk::factory()->for($forecast, 'forecast')->create()->risk_level, ['faible', 'moyen', 'eleve']);
    }

    public function test_weather_alert_relations(): void
    {
        $admin = $this->admin();
        $n = Neighborhood::factory()->create();
        $forecast = WeatherForecast::factory()->create(['neighborhood_id' => $n->id]);
        $alert = WeatherAlert::factory()->create([
            'neighborhood_id' => $n->id, 'weather_forecast_id' => $forecast->id, 'created_by' => $admin->id,
        ]);

        $this->assertTrue($alert->neighborhood->is($n));
        $this->assertTrue($alert->forecast->is($forecast));
        $this->assertTrue($alert->creator->is($admin));
    }

    public function test_automatic_alert_has_no_author_and_a_chosen_level(): void
    {
        $n = Neighborhood::factory()->create();
        $alert = WeatherAlert::factory()->auto()->level('warning')->create(['neighborhood_id' => $n->id]);

        $this->assertSame('auto', $alert->source);
        $this->assertSame('warning', $alert->level);
        $this->assertNull($alert->creator);
    }

    public function test_alert_factory_picks_the_first_admin_as_author(): void
    {
        $admin = $this->admin();
        $alert = WeatherAlert::factory()->create(['neighborhood_id' => Neighborhood::factory()->create()->id]);

        $this->assertSame($admin->id, $alert->created_by);
    }

    public function test_alert_threshold_casts_the_temperature_to_float(): void
    {
        $threshold = AlertThreshold::create(['level' => 'warning', 'temp_max' => '35']);

        $this->assertSame(35.0, $threshold->fresh()->temp_max);
    }

    public function test_forecast_casts(): void
    {
        $n = Neighborhood::factory()->create();
        $f = WeatherForecast::create([
            'neighborhood_id' => $n->id, 'date' => '2026-07-10', 'temp_max' => '40', 'temp_min' => '29', 'level' => 'canicule',
        ])->fresh();

        $this->assertSame(40.0, $f->temp_max);
        $this->assertSame(29.0, $f->temp_min);
        $this->assertSame('2026-07-10', $f->date->toDateString());
        $this->assertTrue($f->neighborhood->is($n));
    }
}
