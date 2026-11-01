<?php

namespace Tests\Feature\Outages;

use App\Events\OutageConfirmed;
use App\Events\OutageResolved;
use App\Models\Neighborhood;
use App\Models\OutageReport;
use App\Models\PowerOutage;
use App\Models\User;
use App\Services\OutageAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OutageAggregationServiceTest extends TestCase
{
    use RefreshDatabase;

    private OutageAggregationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->service = app(OutageAggregationService::class);
    }

    private function trusted(Neighborhood $n): User
    {
        return User::factory()->create(['neighborhood_id' => $n->id, 'created_at' => now()->subDays(3)]);
    }

    public function test_first_report_creates_an_outage_with_an_automatic_eta_and_a_trust_weight(): void
    {
        $n = Neighborhood::factory()->create();
        $outage = $this->service->report($this->trusted($n), $n, null, null);

        $this->assertSame('reported', $outage->status);
        $this->assertSame('user', $outage->source);
        $this->assertNotNull($outage->estimated_end_time);
        $this->assertSame(1, OutageReport::where('power_outage_id', $outage->id)->count());
        $this->assertSame(1.0, OutageReport::first()->trust_weight);
    }

    public function test_a_manager_eta_is_not_overwritten(): void
    {
        $n = Neighborhood::factory()->create();
        $first = $this->service->report($this->trusted($n), $n, null, null);
        $eta = now()->addHours(5)->startOfMinute();
        $first->update(['estimated_end_time' => $eta]);

        $again = $this->service->report($this->trusted($n), $n, null, null);

        $this->assertSame($first->id, $again->id);
        $this->assertTrue($again->estimated_end_time->equalTo($eta));
    }

    public function test_reports_without_coordinates_join_the_existing_outage_of_the_neighborhood(): void
    {
        $n = Neighborhood::factory()->create();
        $a = $this->service->report($this->trusted($n), $n, null, null);
        $b = $this->service->report($this->trusted($n), $n, null, null);

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, PowerOutage::count());
    }

    public function test_reports_with_coordinates_only_join_an_outage_within_500_meters(): void
    {
        $n = Neighborhood::factory()->create(['latitude' => 36.80, 'longitude' => 10.18]);

        $first = $this->service->report($this->trusted($n), $n, 36.8000, 10.1800);
        $near = $this->service->report($this->trusted($n), $n, 36.8010, 10.1810);   // ~140 m
        $far = $this->service->report($this->trusted($n), $n, 36.9000, 10.1800);    // ~11 km

        $this->assertSame($first->id, $near->id);
        $this->assertNotSame($first->id, $far->id);
        $this->assertSame(2, PowerOutage::count());
    }

    public function test_an_outage_without_coordinates_is_skipped_when_the_new_report_has_some(): void
    {
        $n = Neighborhood::factory()->create(['latitude' => 36.80, 'longitude' => 10.18]);

        $noCoords = $this->service->report($this->trusted($n), $n, null, null);
        $withCoords = $this->service->report($this->trusted($n), $n, 36.80, 10.18);

        $this->assertNotSame($noCoords->id, $withCoords->id);
    }

    public function test_an_old_outage_is_not_reused(): void
    {
        $n = Neighborhood::factory()->create();
        $old = $this->service->report($this->trusted($n), $n, null, null);
        $old->update(['started_at' => now()->subHours(2)]);

        $new = $this->service->report($this->trusted($n), $n, null, null);

        $this->assertNotSame($old->id, $new->id);
    }

    public function test_confirm_and_resolve_dispatch_their_event_only_once(): void
    {
        Event::fake([OutageConfirmed::class, OutageResolved::class]);
        $n = Neighborhood::factory()->create();
        $outage = PowerOutage::create(['neighborhood_id' => $n->id, 'status' => 'reported', 'source' => 'user', 'started_at' => now()]);

        $this->service->confirm($outage);
        $this->service->confirm($outage);
        $this->assertSame('confirmed', $outage->fresh()->status);
        Event::assertDispatchedTimes(OutageConfirmed::class, 1);

        $this->service->resolve($outage);
        $this->service->resolve($outage);
        $this->assertSame('resolved', $outage->fresh()->status);
        $this->assertNotNull($outage->fresh()->resolved_at);
        Event::assertDispatchedTimes(OutageResolved::class, 1);
    }

    public function test_reports_on_an_already_confirmed_outage_do_not_confirm_again(): void
    {
        Event::fake([OutageConfirmed::class]);
        $n = Neighborhood::factory()->create();
        $outage = PowerOutage::create(['neighborhood_id' => $n->id, 'status' => 'confirmed', 'source' => 'user', 'started_at' => now(), 'confirmed_at' => now()]);

        $result = $this->service->report($this->trusted($n), $n, null, null);

        $this->assertSame($outage->id, $result->id);
        Event::assertNotDispatched(OutageConfirmed::class);
    }

    public function test_stale_outages_are_closed_but_active_ones_are_kept(): void
    {
        Event::fake([OutageResolved::class]);
        $n = Neighborhood::factory()->create();
        $user = $this->trusted($n);

        $make = function (string $startedAgo, ?string $reportAgo) use ($n, $user) {
            $o = PowerOutage::create(['neighborhood_id' => $n->id, 'status' => 'reported', 'source' => 'user', 'started_at' => now()->sub($startedAgo)]);
            if ($reportAgo) {
                $r = OutageReport::create(['power_outage_id' => $o->id, 'user_id' => $user->id, 'trust_weight' => 1.0, 'trust_flags' => []]);
                $r->created_at = now()->sub($reportAgo);
                $r->save();
            }

            return $o;
        };

        $stale = $make('4 hours', '3 hours');   // silencieuse depuis 3 h
        $noReport = $make('5 hours', null);     // jamais de signalement
        $recent = $make('4 hours', '10 minutes'); // signalement récent
        $young = $make('1 hour', '30 minutes');   // trop jeune

        $closed = $this->service->closeStaleOutages();

        $this->assertSame(2, $closed);
        $this->assertSame('resolved', $stale->fresh()->status);
        $this->assertSame('resolved', $noReport->fresh()->status);
        $this->assertSame('reported', $recent->fresh()->status);
        $this->assertSame('reported', $young->fresh()->status);
    }

    public function test_close_stale_outages_command_reports_how_many_were_closed(): void
    {
        $n = Neighborhood::factory()->create();
        PowerOutage::create(['neighborhood_id' => $n->id, 'status' => 'confirmed', 'source' => 'user', 'started_at' => now()->subHours(6)]);

        $this->artisan('app:close-stale-outages')
            ->expectsOutput('1 coupure(s) clôturée(s) automatiquement.')
            ->assertSuccessful();
    }
}