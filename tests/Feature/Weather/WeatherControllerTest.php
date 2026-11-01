<?php

namespace Tests\Feature\Weather;

use App\Models\Neighborhood;
use App\Models\WeatherAlert;
use App\Models\WeatherForecast;
use App\Services\AdviceService;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class WeatherControllerTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function forecast(Neighborhood $n, int $daysFromToday = 0, string $level = 'canicule'): WeatherForecast
    {
        return WeatherForecast::factory()->ofLevel($level)->create([
            'neighborhood_id' => $n->id,
            'date' => today()->addDays($daysFromToday)->toDateString(),
        ]);
    }

    // --- index -------------------------------------------------------------

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('weather.index'))->assertRedirect(route('login'));
        $this->post(route('weather.advice'))->assertRedirect(route('login'));
    }

    public function test_index_only_shows_upcoming_forecasts_of_the_users_neighborhood(): void
    {
        $mine = Neighborhood::factory()->create();
        $other = Neighborhood::factory()->create();
        $today = $this->forecast($mine, 0);
        $tomorrow = $this->forecast($mine, 1, 'warning');
        $this->forecast($mine, -2);           // passée
        $this->forecast($other, 1);           // autre quartier

        $this->actingAs($this->resident($mine))->get(route('weather.index'))
            ->assertOk()
            ->assertViewIs('weather.index')
            ->assertViewHas('forecasts', fn ($f) => $f->pluck('id')->all() === [$today->id, $tomorrow->id])
            ->assertViewHas('riskScores', fn ($scores) => $scores->keys()->all() === [$mine->id]);
    }

    public function test_index_shows_every_neighborhood_when_the_user_has_none(): void
    {
        $a = Neighborhood::factory()->create();
        $b = Neighborhood::factory()->create();
        $this->forecast($a, 0);
        $this->forecast($b, 0);

        $this->actingAs($this->resident())->get(route('weather.index'))
            ->assertOk()
            ->assertViewHas('forecasts', fn ($f) => $f->count() === 2)
            ->assertViewHas('riskScores', fn ($s) => $s->count() === 2);
    }

    public function test_index_lists_at_most_five_recent_alerts_of_the_neighborhood(): void
    {
        $mine = Neighborhood::factory()->create();
        $other = Neighborhood::factory()->create();
        WeatherAlert::factory()->count(7)->create(['neighborhood_id' => $mine->id]);
        WeatherAlert::factory()->create(['neighborhood_id' => $other->id]);
        $old = WeatherAlert::factory()->create(['neighborhood_id' => $mine->id]);
        $old->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->actingAs($this->resident($mine))->get(route('weather.index'))
            ->assertViewHas('alerts', fn ($alerts) => $alerts->count() === 5
                && $alerts->every(fn ($a) => $a->neighborhood_id === $mine->id && $a->id !== $old->id));
    }

    // --- refresh -----------------------------------------------------------

    public function test_refresh_is_reserved_to_admin_and_gestionnaire(): void
    {
        $this->actingAs($this->resident())->post(route('weather.refresh'))->assertForbidden();
    }

    public function test_refresh_updates_the_weather(): void
    {
        $this->mock(WeatherService::class, fn ($m) => $m->shouldReceive('refreshAll')->once()->andReturn(7));

        $this->actingAs($this->gestionnaire())->from(route('weather.index'))
            ->post(route('weather.refresh'))
            ->assertRedirect(route('weather.index'))
            ->assertSessionHas('status', 'Météo mise à jour.');
    }

    public function test_refresh_reports_an_unreachable_api(): void
    {
        Log::spy();
        $this->mock(WeatherService::class, fn ($m) => $m->shouldReceive('refreshAll')->andThrow(new ConnectionException('timeout')));

        $this->actingAs($this->admin())->post(route('weather.refresh'))
            ->assertSessionHas('status', "Impossible de joindre l'API météo, réessaie plus tard.");

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_refresh_reports_any_other_failure(): void
    {
        Log::spy();
        $this->mock(WeatherService::class, fn ($m) => $m->shouldReceive('refreshAll')->andThrow(new \RuntimeException('boom')));

        $this->actingAs($this->admin())->post(route('weather.refresh'))
            ->assertSessionHas('status', 'Erreur lors de la mise à jour de la météo.');

        Log::shouldHaveReceived('error')->once();
    }

    // --- advice ------------------------------------------------------------

    public function test_advice_without_forecast_explains_why_nothing_is_generated(): void
    {
        $this->actingAs($this->resident(Neighborhood::factory()->create()))
            ->post(route('weather.advice'))
            ->assertSessionHas('status', 'Aucune prévision à venir : impossible de générer des conseils.');
    }

    public function test_advice_returns_the_generated_text(): void
    {
        $n = Neighborhood::factory()->create();
        $this->forecast($n, 1);
        $this->mock(AdviceService::class, fn ($m) => $m->shouldReceive('generate')->once()->andReturn('1. Buvez de l\'eau.'));

        $this->actingAs($this->resident($n))->post(route('weather.advice'))
            ->assertSessionHas('advice', '1. Buvez de l\'eau.');
    }

    public function test_advice_reports_an_unreachable_service(): void
    {
        Log::spy();
        $n = Neighborhood::factory()->create();
        $this->forecast($n, 1);
        $this->mock(AdviceService::class, fn ($m) => $m->shouldReceive('generate')->andThrow(new ConnectionException('timeout')));

        $this->actingAs($this->resident($n))->post(route('weather.advice'))
            ->assertSessionHas('status', 'Impossible de joindre le service de conseils, réessaie plus tard.');

        Log::shouldHaveReceived('warning')->once();
    }

    public function test_advice_reports_any_other_failure(): void
    {
        Log::spy();
        $n = Neighborhood::factory()->create();
        $this->forecast($n, 1);
        $this->mock(AdviceService::class, fn ($m) => $m->shouldReceive('generate')->andThrow(new \RuntimeException('boom')));

        $this->actingAs($this->resident($n))->post(route('weather.advice'))
            ->assertSessionHas('status', 'Erreur lors de la génération des conseils.');

        Log::shouldHaveReceived('error')->once();
    }
}
