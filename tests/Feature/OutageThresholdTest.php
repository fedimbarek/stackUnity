<?php

namespace Tests\Feature;

use App\Models\Neighborhood;
use App\Models\User;
use App\Services\OutageAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OutageThresholdTest extends TestCase
{
    use RefreshDatabase;

    public function test_outage_turns_red_after_3_distinct_users(): void
    {
        Event::fake(); // pas de notifications pendant le test

        $service = app(OutageAggregationService::class);
        $neighborhood = Neighborhood::factory()->create();

        // 2 users => reste "reported" (jaune)
        foreach (range(1, 2) as $i) {
            $user = User::factory()->create(["neighborhood_id" => $neighborhood->id]);
            $outage = $service->report($user, $neighborhood, null, null);
            $this->assertSame("reported", $outage->status);
        }

        // 3eme user => "confirmed" (rouge) directement
        $user = User::factory()->create(["neighborhood_id" => $neighborhood->id]);
        $outage = $service->report($user, $neighborhood, null, null);

        $this->assertSame("confirmed", $outage->status);
        $this->assertNotNull($outage->confirmed_at);
    }

    public function test_same_user_reporting_5_times_does_not_confirm(): void
    {
        Event::fake();

        $service = app(OutageAggregationService::class);
        $neighborhood = Neighborhood::factory()->create();
        $user = User::factory()->create(["neighborhood_id" => $neighborhood->id]);

        foreach (range(1, 5) as $i) {
            $outage = $service->report($user, $neighborhood, null, null);
        }

        $this->assertSame("reported", $outage->status);
    }
}