<?php

namespace Tests\Unit\Services;

use App\Models\Neighborhood;
use App\Models\WeatherForecast;
use App\Services\AdviceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdviceServiceGeminiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.key' => 'test-key',
            'services.gemini.model' => 'gemini-test',
            'services.weather.heat_warning' => 35,
        ]);
        Http::preventStrayRequests();
    }

    private function forecasts(string $level = 'canicule', float $temp = 41)
    {
        $forecast = new WeatherForecast([
            'date' => Carbon::parse('2026-07-10'), 'temp_max' => $temp, 'temp_min' => $temp - 10, 'level' => $level,
        ]);
        $forecast->setRelation('neighborhood', new Neighborhood(['name' => 'Menzah']));

        return collect([$forecast]);
    }

    private function geminiReply(?string $text): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
    }

    public function test_it_returns_the_text_generated_by_gemini(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiReply("1. Buvez.\n2. Restez au frais."))]);

        $advice = app(AdviceService::class)->generate($this->forecasts());

        $this->assertSame("1. Buvez.\n2. Restez au frais.", $advice);
    }

    public function test_the_request_carries_the_key_the_model_and_the_forecasts(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiReply('1. OK'))]);

        app(AdviceService::class)->generate($this->forecasts());

        Http::assertSent(function ($request) {
            $prompt = $request['contents'][0]['parts'][0]['text'];

            return $request->hasHeader('x-goog-api-key', 'test-key')
                && str_contains($request->url(), 'models/gemini-test:generateContent')
                && str_contains($prompt, 'Menzah')
                && str_contains($prompt, '10/07 : max 41°C, min 31°C, niveau canicule');
        });
    }

    public function test_an_empty_answer_falls_back_to_local_advice(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiReply(null))]);

        $advice = app(AdviceService::class)->generate($this->forecasts());

        $this->assertStringContainsString('Conseils de prévention', $advice);
        $this->assertStringContainsString('température maximale prévue : 41°C', $advice);
    }

    public function test_an_api_error_falls_back_to_local_advice(): void
    {
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota'], 429)]);

        $advice = app(AdviceService::class)->generate($this->forecasts());

        $this->assertStringContainsString('Conseils de prévention', $advice);
        $this->assertStringContainsString('Préparez-vous à une coupure', $advice);
    }

    public function test_the_fallback_adds_the_canicule_advice_only_when_needed(): void
    {
        config(['services.gemini.key' => null]);
        $service = app(AdviceService::class);

        $this->assertStringContainsString('points de fraîcheur', $service->generate($this->forecasts('canicule', 41)));
        $this->assertStringNotContainsString('points de fraîcheur', $service->generate($this->forecasts('warning', 36)));
        $this->assertStringNotContainsString('Préparez-vous à une coupure', $service->generate($this->forecasts('normal', 25)));
    }
}
