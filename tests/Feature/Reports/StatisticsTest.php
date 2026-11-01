<?php

namespace Tests\Feature\Reports;

use App\Models\Neighborhood;
use App\Models\PowerOutage;
use App\Services\StatisticsExporter;
use App\Services\StatisticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\UsesTemporaryStorage;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase, UsesTemporaryStorage;

    private function outage(Neighborhood $n, string $status, string $startedAt, ?string $resolvedAt = null): PowerOutage
    {
        return PowerOutage::create([
            'neighborhood_id' => $n->id, 'status' => $status, 'source' => 'user',
            'started_at' => $startedAt, 'resolved_at' => $resolvedAt,
        ]);
    }

    public function test_kpis_are_computed_for_a_period(): void
    {
        $n = Neighborhood::factory()->create();
        $this->outage($n, 'reported', '2026-07-06 10:00:00');
        $this->outage($n, 'confirmed', '2026-07-07 10:00:00');
        $this->outage($n, 'resolved', '2026-07-08 10:00:00', '2026-07-08 11:00:00'); // 60 min
        $this->outage($n, 'resolved', '2026-07-09 10:00:00', '2026-07-09 12:00:00'); // 120 min
        $this->outage($n, 'reported', '2026-06-01 10:00:00'); // hors période

        $k = app(StatisticsService::class)->kpis(null, Carbon::parse('2026-07-06'), Carbon::parse('2026-07-12'));

        $this->assertSame(4, $k['total_outages']);
        $this->assertSame(3, $k['confirmed_outages']);
        $this->assertSame(2, $k['resolved_outages']);
        $this->assertSame(75.0, $k['confirmation_rate']);
        $this->assertSame(90, (int) $k['avg_resolution_minutes']);
    }

    public function test_kpis_without_data_and_filtered_by_neighborhood(): void
    {
        $a = Neighborhood::factory()->create();
        $b = Neighborhood::factory()->create();
        $this->outage($a, 'reported', '2026-07-06 10:00:00');

        $service = app(StatisticsService::class);
        $empty = $service->kpis($b->id, Carbon::parse('2026-07-06'), Carbon::parse('2026-07-12'));
        $this->assertSame(0, $empty['total_outages']);
        $this->assertSame(0, $empty['confirmation_rate']);
        $this->assertNull($empty['avg_resolution_minutes']);

        $only = $service->kpis($a->id, Carbon::parse('2026-07-06'), Carbon::parse('2026-07-12'));
        $this->assertSame(1, $only['total_outages']);
    }

    public function test_comparison_with_the_previous_period(): void
    {
        $n = Neighborhood::factory()->create();
        // semaine précédente : 2 coupures ; semaine courante : 3
        $this->outage($n, 'reported', '2026-06-29 10:00:00');
        $this->outage($n, 'reported', '2026-06-30 10:00:00');
        foreach (['2026-07-06', '2026-07-07', '2026-07-08'] as $d) {
            $this->outage($n, 'reported', "$d 10:00:00");
        }

        $c = app(StatisticsService::class)->compareWithPreviousPeriod(null, Carbon::parse('2026-07-06'), Carbon::parse('2026-07-12'));

        $this->assertSame(3, $c['current']['total_outages']);
        $this->assertSame(2, $c['previous']['total_outages']);
        $this->assertSame(50.0, $c['variation_percent']);
    }

    public function test_variation_is_100_when_the_previous_period_was_empty_and_0_when_both_are(): void
    {
        $service = app(StatisticsService::class);
        $n = Neighborhood::factory()->create();

        $none = $service->compareWithPreviousPeriod(null, Carbon::parse('2026-07-06'), Carbon::parse('2026-07-12'));
        $this->assertSame(0, $none['variation_percent']);

        Cache::flush(); // kpis() est mis en cache 10 min : on repart d'un cache vide
        $this->outage($n, 'reported', '2026-07-07 10:00:00');
        $grown = $service->compareWithPreviousPeriod(null, Carbon::parse('2026-07-06'), Carbon::parse('2026-07-12'));
        $this->assertSame(100, $grown['variation_percent']);
    }

    public function test_outages_are_grouped_by_neighborhood_in_descending_order(): void
    {
        $a = Neighborhood::factory()->create(['name' => 'Alpha']);
        $b = Neighborhood::factory()->create(['name' => 'Beta']);
        $this->outage($a, 'reported', '2026-07-07 10:00:00');
        foreach (range(1, 3) as $i) {
            $this->outage($b, 'reported', '2026-07-08 10:00:00');
        }

        $rows = app(StatisticsService::class)->byNeighborhood(Carbon::parse('2026-07-06'), Carbon::parse('2026-07-12'));

        $this->assertSame(['Beta', 'Alpha'], $rows->pluck('neighborhood')->all());
        $this->assertSame([3, 1], $rows->pluck('total')->map(fn ($t) => (int) $t)->all());
    }

    public function test_csv_export_contains_a_bom_headers_and_rows(): void
    {
        $n = Neighborhood::factory()->create(['name' => 'Menzah']);
        $this->outage($n, 'reported', '2026-07-07 10:00:00');

        $name = app(StatisticsExporter::class)->exportToCsv(Carbon::parse('2026-07-06'), Carbon::parse('2026-07-12'), 'stats.csv');

        $this->assertSame('stats.csv', $name);
        $content = file_get_contents(storage_path('reports/stats.csv'));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Quartier,"Nombre de coupures"', $content);
        $this->assertStringContainsString('Menzah,1', $content);
    }
}