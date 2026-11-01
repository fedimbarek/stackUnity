<?php

namespace Tests\Unit\Weather;

use App\Models\WeatherForecast;
use App\Services\AdviceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdviceServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Utiliser les conseils locaux sans clé IA.
        config()->set('services.gemini.key', null);
        config()->set('services.weather.heat_warning', 35);

        // Interdire les appels réseau réels.
        Http::preventStrayRequests();
    }

    private function forecast(float $temp, string $level)
    {
        return new WeatherForecast([
            'date' => Carbon::parse('2026-11-01'),
            'temp_max' => $temp,
            'temp_min' => $temp - 10,
            'level' => $level,
        ]);
    }

    public function test_aucune_prevision(): void
    {
        $result = app(AdviceService::class)
            ->generate(collect());

        $this->assertStringContainsString(
            'Aucune prévision',
            $result
        );
    }

    public function test_conseils_temperature_normale(): void
    {
        $result = app(AdviceService::class)->generate(
            collect([$this->forecast(25, 'normal')])
        );

        $this->assertStringContainsString(
            "Buvez de l'eau",
            $result
        );

        $this->assertStringNotContainsString(
            'Préparez-vous à une coupure',
            $result
        );
    }

    public function test_conseils_forte_chaleur(): void
    {
        $result = app(AdviceService::class)->generate(
            collect([$this->forecast(36, 'warning')])
        );

        $this->assertStringContainsString(
            'Préparez-vous à une coupure',
            $result
        );
    }

    public function test_conseils_canicule(): void
    {
        $result = app(AdviceService::class)->generate(
            collect([$this->forecast(40, 'canicule')])
        );

        $this->assertStringContainsString(
            'points de fraîcheur',
            $result
        );
    }

    public function test_fallback_sans_cle_api(): void
    {
        $result = app(AdviceService::class)->generate(
            collect([$this->forecast(39, 'canicule')])
        );

        $this->assertNotEmpty($result);
        Http::assertNothingSent();
    }
}
