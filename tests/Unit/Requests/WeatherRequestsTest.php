<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\AlertThresholdRequest;
use App\Http\Requests\OutageRiskRequest;
use App\Http\Requests\WeatherAlertRequest;
use App\Http\Requests\WeatherForecastRequest;
use App\Models\Neighborhood;
use App\Models\WeatherForecast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class WeatherRequestsTest extends TestCase
{
    use RefreshDatabase;

    private function validate(string $requestClass, array $data, ?object $route = null)
    {
        $request = $requestClass::create('/', 'POST', $data);

        if ($route) {
            $request->setRouteResolver(fn () => new class($route) {
                public function __construct(private object $model) {}
                public function parameter($name, $default = null) { return $this->model; }
            });
        }

        return Validator::make($data, $request->rules(), $request->messages());
    }

    // --- AlertThresholdRequest ---------------------------------------------

    public function test_threshold_request_is_authorized_and_accepts_valid_data(): void
    {
        $this->assertTrue((new AlertThresholdRequest())->authorize());
        $this->assertTrue($this->validate(AlertThresholdRequest::class, ['warning' => 35, 'canicule' => 38])->passes());
    }

    public function test_threshold_request_rejects_bad_values_with_french_messages(): void
    {
        $v = $this->validate(AlertThresholdRequest::class, []);
        $this->assertSame('Le seuil de forte chaleur est obligatoire.', $v->errors()->first('warning'));
        $this->assertSame('Le seuil de canicule est obligatoire.', $v->errors()->first('canicule'));

        $v = $this->validate(AlertThresholdRequest::class, ['warning' => 5, 'canicule' => 70]);
        $this->assertSame('Le seuil doit être compris entre 10 et 60 °C.', $v->errors()->first('warning'));
        $this->assertSame('Le seuil doit être compris entre 10 et 60 °C.', $v->errors()->first('canicule'));

        $v = $this->validate(AlertThresholdRequest::class, ['warning' => 40, 'canicule' => 38]);
        $this->assertSame('Le seuil de canicule doit être supérieur au seuil de forte chaleur.', $v->errors()->first('canicule'));
    }

    // --- OutageRiskRequest -------------------------------------------------

    public function test_outage_risk_request_accepts_valid_data(): void
    {
        $forecast = WeatherForecast::factory()->create(['neighborhood_id' => Neighborhood::factory()->create()->id]);

        $this->assertTrue((new OutageRiskRequest())->authorize());
        $this->assertTrue($this->validate(OutageRiskRequest::class, [
            'weather_forecast_id' => $forecast->id,
            'risk_level' => 'eleve',
            'description' => 'Risque de délestage entre 12h et 17h.',
        ])->passes());
    }

    public function test_outage_risk_request_rejects_invalid_data(): void
    {
        $v = $this->validate(OutageRiskRequest::class, []);
        $this->assertSame('Choisis une prévision.', $v->errors()->first('weather_forecast_id'));
        $this->assertSame('La description est obligatoire.', $v->errors()->first('description'));

        $v = $this->validate(OutageRiskRequest::class, [
            'weather_forecast_id' => 999, 'risk_level' => 'extreme', 'description' => 'court',
        ]);
        $this->assertSame("Cette prévision n'existe pas.", $v->errors()->first('weather_forecast_id'));
        $this->assertSame('Niveau de risque invalide.', $v->errors()->first('risk_level'));
        $this->assertSame('La description doit contenir au moins 10 caractères.', $v->errors()->first('description'));

        $v = $this->validate(OutageRiskRequest::class, [
            'weather_forecast_id' => 999, 'risk_level' => 'faible', 'description' => str_repeat('a', 501),
        ]);
        $this->assertSame('La description ne doit pas dépasser 500 caractères.', $v->errors()->first('description'));
    }

    // --- WeatherAlertRequest -----------------------------------------------

    public function test_weather_alert_request_accepts_valid_data(): void
    {
        $n = Neighborhood::factory()->create();

        $this->assertTrue((new WeatherAlertRequest())->authorize());
        $this->assertTrue($this->validate(WeatherAlertRequest::class, [
            'neighborhood_id' => $n->id, 'level' => 'canicule', 'message' => 'Canicule prévue demain.',
        ])->passes());
    }

    public function test_weather_alert_request_rejects_invalid_data(): void
    {
        $v = $this->validate(WeatherAlertRequest::class, []);
        $this->assertSame('Choisis un quartier.', $v->errors()->first('neighborhood_id'));
        $this->assertSame('Le message est obligatoire.', $v->errors()->first('message'));

        $n = Neighborhood::factory()->create();
        $v = $this->validate(WeatherAlertRequest::class, [
            'neighborhood_id' => $n->id, 'level' => 'normal', 'message' => 'court',
        ]);
        $this->assertSame('Niveau invalide.', $v->errors()->first('level'));
        $this->assertSame('Le message doit contenir au moins 10 caractères.', $v->errors()->first('message'));

        $v = $this->validate(WeatherAlertRequest::class, [
            'neighborhood_id' => $n->id, 'level' => 'warning', 'message' => str_repeat('a', 501),
        ]);
        $this->assertSame('Le message ne doit pas dépasser 500 caractères.', $v->errors()->first('message'));
    }

    // --- WeatherForecastRequest --------------------------------------------

    private function forecastData(Neighborhood $n, array $override = []): array
    {
        return array_merge([
            'neighborhood_id' => $n->id, 'date' => '2026-07-10',
            'temp_max' => 40, 'temp_min' => 28, 'level' => 'canicule',
        ], $override);
    }

    public function test_weather_forecast_request_accepts_valid_data(): void
    {
        $n = Neighborhood::factory()->create();

        $this->assertTrue((new WeatherForecastRequest())->authorize());
        $this->assertTrue($this->validate(WeatherForecastRequest::class, $this->forecastData($n))->passes());
    }

    public function test_weather_forecast_request_rejects_a_second_forecast_for_the_same_day(): void
    {
        $n = Neighborhood::factory()->create();
        WeatherForecast::factory()->create(['neighborhood_id' => $n->id, 'date' => '2026-07-10']);

        $v = $this->validate(WeatherForecastRequest::class, $this->forecastData($n));

        $this->assertSame('Une prévision existe déjà pour ce quartier à cette date.', $v->errors()->first('date'));
    }

    public function test_weather_forecast_request_ignores_the_forecast_being_edited(): void
    {
        $n = Neighborhood::factory()->create();
        $existing = WeatherForecast::factory()->create(['neighborhood_id' => $n->id, 'date' => '2026-07-10']);

        $v = $this->validate(WeatherForecastRequest::class, $this->forecastData($n), $existing);

        $this->assertTrue($v->passes());
    }

    public function test_weather_forecast_request_rejects_invalid_data(): void
    {
        $v = $this->validate(WeatherForecastRequest::class, []);
        $this->assertSame('Choisis un quartier.', $v->errors()->first('neighborhood_id'));
        $this->assertSame('La date est obligatoire.', $v->errors()->first('date'));
        $this->assertSame('La température maximale est obligatoire.', $v->errors()->first('temp_max'));
        $this->assertSame('La température minimale est obligatoire.', $v->errors()->first('temp_min'));

        $n = Neighborhood::factory()->create();
        $v = $this->validate(WeatherForecastRequest::class, $this->forecastData($n, [
            'neighborhood_id' => 999, 'temp_max' => 80, 'temp_min' => -30, 'level' => 'extreme',
        ]));
        $this->assertSame("Ce quartier n'existe pas.", $v->errors()->first('neighborhood_id'));
        $this->assertSame('La température doit être comprise entre -10 et 60 °C.', $v->errors()->first('temp_max'));
        $this->assertSame('La température doit être comprise entre -10 et 60 °C.', $v->errors()->first('temp_min'));
        $this->assertSame('Niveau invalide.', $v->errors()->first('level'));

        $v = $this->validate(WeatherForecastRequest::class, $this->forecastData($n, ['temp_max' => 30, 'temp_min' => 35]));
        $this->assertSame('La température minimale doit être inférieure ou égale à la maximale.', $v->errors()->first('temp_min'));
    }
}
