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
            fn ($temp, $index) => new WeatherForecast([
                'date' => Carbon::parse('2026-11-01')
                    ->addDays($index),
                'temp_max' => $temp,
                'temp_min' => $temp - 10,
            ])
        );
    }

    public function test_score_faible(): void
    {
        $result = $this->service->calculate(
            $this->forecasts([24.8, 23, 22]),
            35,
            38
        );

        $this->assertEquals(21, $result['score']);
        $this->assertEquals('faible', $result['level']);
    }

    public function test_score_modere(): void
    {
        $result = $this->service->calculate(
            $this->forecasts([35, 33, 32]),
            35,
            38
        );

        $this->assertEquals(30, $result['score']);
        $this->assertEquals('modere', $result['level']);
    }

    public function test_score_critique(): void
    {
        $result = $this->service->calculate(
            $this->forecasts([36, 39, 38]),
            35,
            38
        );

        $this->assertEquals(90, $result['score']);
        $this->assertEquals('critique', $result['level']);
    }

    public function test_score_ne_depasse_pas_100(): void
    {
        $result = $this->service->calculate(
            $this->forecasts([45, 46, 47]),
            35,
            38
        );

        $this->assertEquals(100, $result['score']);
    }

    public function test_aucune_prevision(): void
    {
        $result = $this->service->calculate(
            collect(),
            35,
            38
        );

        $this->assertEquals(0, $result['score']);
        $this->assertEquals(
            'indisponible',
            $result['level']
        );
    }

    public function test_seulement_trois_jours(): void
    {
        $result = $this->service->calculate(
            $this->forecasts([25, 26, 27, 45, 46]),
            35,
            38
        );

        $this->assertEquals(3, $result['days']);
        $this->assertEquals('faible', $result['level']);
    }
}
