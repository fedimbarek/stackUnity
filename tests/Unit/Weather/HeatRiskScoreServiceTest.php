<?php

namespace Tests\Unit\Weather;

use App\Models\WeatherForecast;
use App\Services\HeatRiskScoreService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class HeatRiskScoreServiceTest extends TestCase
{
    private HeatRiskScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HeatRiskScoreService();
    }

    private function forecasts(array $temperatures)
    {
        return collect($temperatures)->map(
            fn ($temperature, $index) => new WeatherForecast([
                'date' => Carbon::parse('2026-11-01')->addDays($index),
                'temp_max' => $temperature,
                'temp_min' => $temperature - 10,
            ])
        );
    }

    public function test_score_faible(): void
    {
        $result = $this->service->calculate($this->forecasts([24.8, 23, 22]), 35, 38);
        $this->assertSame(21, $result['score']);
        $this->assertSame('faible', $result['level']);
    }

    public function test_score_modere(): void
    {
        $result = $this->service->calculate($this->forecasts([35, 33, 32]), 35, 38);
        $this->assertSame(30, $result['score']);
        $this->assertSame('modere', $result['level']);
    }

    public function test_score_critique(): void
    {
        $result = $this->service->calculate($this->forecasts([36, 39, 38]), 35, 38);
        $this->assertSame(90, $result['score']);
        $this->assertSame('critique', $result['level']);
    }

    public function test_score_plafonne_a_100(): void
    {
        $result = $this->service->calculate($this->forecasts([45, 46, 47]), 35, 38);
        $this->assertSame(100, $result['score']);
    }

    public function test_previsions_absentes(): void
    {
        $result = $this->service->calculate(collect(), 35, 38);
        $this->assertSame('indisponible', $result['level']);
        $this->assertSame(0, $result['days']);
    }

    public function test_seuls_les_trois_premiers_jours_sont_utilises(): void
    {
        $result = $this->service->calculate($this->forecasts([25, 26, 27, 45, 46]), 35, 38);
        $this->assertSame(3, $result['days']);
        $this->assertSame('faible', $result['level']);
    }
}
