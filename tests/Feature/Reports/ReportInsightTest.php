<?php

namespace Tests\Feature\Reports;

use App\Models\Neighborhood;
use App\Models\PowerOutage;
use App\Models\Report;
use App\Services\ReportInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class ReportInsightTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function outage(Neighborhood $n, string $startedAt): void
    {
        PowerOutage::create([
            'neighborhood_id' => $n->id, 'status' => 'reported', 'source' => 'user', 'started_at' => $startedAt,
        ]);
    }

    private function report(?Neighborhood $n = null): Report
    {
        return Report::factory()->create([
            'neighborhood_id' => $n?->id,
            'generated_by' => $this->admin()->id,
            'period_start' => '2026-07-06',
            'period_end' => '2026-07-12',
        ]);
    }

    private function geminiAnswer(string $text): array
    {
        return ['candidates' => [['content' => ['parts' => [['text' => $text]]]]]];
    }

    public function test_fallback_summary_is_used_without_api_key_and_stored(): void
    {
        config(['services.gemini.key' => null]);
        Http::fake();
        $n = Neighborhood::factory()->create(['name' => 'Menzah']);
        $this->outage($n, '2026-07-07 10:00:00');
        $report = $this->report();

        $summary = app(ReportInsightService::class)->generate($report);

        Http::assertNothingSent();
        $this->assertStringContainsString('en hausse', $summary);
        $this->assertStringContainsString('Menzah', $summary);
        $this->assertStringContainsString('tous les quartiers', $summary);
        $report->refresh();
        $this->assertSame($summary, $report->ai_summary);
        $this->assertNotNull($report->ai_generated_at);
    }

    public function test_fallback_trend_is_down_when_the_previous_period_was_busier(): void
    {
        config(['services.gemini.key' => null]);
        $n = Neighborhood::factory()->create();
        $this->outage($n, '2026-06-30 10:00:00');
        $this->outage($n, '2026-06-29 10:00:00');
        $this->outage($n, '2026-07-07 10:00:00');

        $summary = app(ReportInsightService::class)->generate($this->report($n));

        $this->assertStringContainsString('en baisse', $summary);
    }

    public function test_fallback_trend_is_stable_without_any_data(): void
    {
        config(['services.gemini.key' => null]);

        $summary = app(ReportInsightService::class)->generate($this->report());

        $this->assertStringContainsString('stable', $summary);
        $this->assertStringContainsString('aucun quartier', $summary);
    }

    public function test_gemini_answer_is_stored_and_the_prompt_contains_the_facts(): void
    {
        config(['services.gemini.key' => 'secret']);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($this->geminiAnswer(' Résumé IA '))]);
        $n = Neighborhood::factory()->create(['name' => 'Bardo']);
        $this->outage($n, '2026-07-07 10:00:00');
        $report = $this->report($n);

        $summary = app(ReportInsightService::class)->generate($report);

        $this->assertSame('Résumé IA', $summary);
        $this->assertSame('Résumé IA', $report->fresh()->ai_summary);
        Http::assertSent(function (Request $request) {
            $prompt = $request['contents'][0]['parts'][0]['text'];

            return str_contains($prompt, 'Périmètre : Bardo')
                && str_contains($prompt, '06/07/2026 au 12/07/2026')
                && str_contains($prompt, 'Coupures : 1');
        });
    }

    public function test_controller_generates_the_insight_and_redirects_back(): void
    {
        config(['services.gemini.key' => null]);
        $admin = $this->admin();
        $report = $this->report();

        $this->actingAs($admin)->from(route('reports.show', $report))
            ->post(route('reports.insight', $report))
            ->assertRedirect(route('reports.show', $report))
            ->assertSessionHas('status', 'report-insight-generated');

        $this->assertNotNull($report->fresh()->ai_summary);
    }

    public function test_controller_is_forbidden_for_residents_and_guests(): void
    {
        $report = $this->report();

        $this->post(route('reports.insight', $report))->assertRedirect(route('login'));
        $this->actingAs($this->resident())->post(route('reports.insight', $report))->assertForbidden();
    }

    public function test_gestionnaire_cannot_generate_an_insight_for_another_neighborhood(): void
    {
        $report = $this->report(Neighborhood::factory()->create());

        $this->actingAs($this->gestionnaire(Neighborhood::factory()->create()))
            ->post(route('reports.insight', $report))
            ->assertNotFound();

        $this->assertNull($report->fresh()->ai_summary);
    }
}
