<?php

namespace Tests\Feature\Outages;

use App\Models\Neighborhood;
use App\Models\OutageReport;
use App\Models\PowerOutage;
use App\Models\User;
use App\Services\ReporterTrustService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporterTrustServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReporterTrustService $service;
    private Neighborhood $neighborhood;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReporterTrustService();
        $this->neighborhood = Neighborhood::factory()->create(['latitude' => 36.80, 'longitude' => 10.18]);
    }

    private function user(array $attrs = []): User
    {
        return User::factory()->create($attrs + ['created_at' => now()->subDays(5)]);
    }

    private function reportFor(User $user, bool $confirmedOutage, int $times = 1): void
    {
        for ($i = 0; $i < $times; $i++) {
            $outage = PowerOutage::create([
                'neighborhood_id' => $this->neighborhood->id,
                'status' => $confirmedOutage ? 'confirmed' : 'reported',
                'source' => 'user',
                'started_at' => now()->subHours(2),
                'confirmed_at' => $confirmedOutage ? now() : null,
            ]);
            OutageReport::create(['power_outage_id' => $outage->id, 'user_id' => $user->id, 'trust_weight' => 1.0, 'trust_flags' => []]);
        }
    }

    public function test_a_normal_user_has_full_weight(): void
    {
        $r = $this->service->evaluate($this->user(), $this->neighborhood, null, null);

        $this->assertSame(1.0, $r['weight']);
        $this->assertSame([], $r['flags']);
    }

    public function test_a_report_from_the_neighborhood_is_not_penalised(): void
    {
        $r = $this->service->evaluate($this->user(), $this->neighborhood, 36.801, 10.181);

        $this->assertSame(1.0, $r['weight']);
    }

    public function test_gps_outside_the_neighborhood_reduces_the_weight(): void
    {
        $outside = $this->service->evaluate($this->user(), $this->neighborhood, 36.85, 10.18); // ~5,5 km
        $this->assertSame(0.6, $outside['weight']);
        $this->assertContains('gps_hors_quartier', $outside['flags']);

        $veryFar = $this->service->evaluate($this->user(), $this->neighborhood, 37.20, 10.18); // ~44 km
        $this->assertSame(0.2, $veryFar['weight']);
        $this->assertContains('gps_tres_loin', $veryFar['flags']);
    }

    public function test_flooding_in_24h_reduces_the_weight(): void
    {
        $user = $this->user();
        $this->reportFor($user, false, 5);

        $r = $this->service->evaluate($user, $this->neighborhood, null, null);

        $this->assertContains('flood_24h', $r['flags']);
        $this->assertContains('reputation_faible', $r['flags']);
        $this->assertSame(0.15, $r['weight']); // 1 x 0,3 x 0,5
    }

    public function test_unverified_email_and_brand_new_account_reduce_the_weight(): void
    {
        $r = $this->service->evaluate(
            User::factory()->unverified()->create(['created_at' => now()]),
            $this->neighborhood, null, null
        );

        $this->assertSame(0.35, $r['weight']); // 1 x 0,5 x 0,7
        $this->assertEqualsCanonicalizing(['email_non_verifie', 'compte_tres_recent'], $r['flags']);
    }

    public function test_a_user_whose_reports_are_mostly_confirmed_gets_a_bonus(): void
    {
        $user = $this->user();
        $this->reportFor($user, true, 3);

        $r = $this->service->evaluate($user, $this->neighborhood, null, null);

        $this->assertSame(1.2, $r['weight']);
        $this->assertContains('reputation_forte', $r['flags']);
    }

    public function test_an_average_reputation_changes_nothing(): void
    {
        $user = $this->user();
        $this->reportFor($user, true, 1);
        $this->reportFor($user, false, 2); // ratio 0,33

        $r = $this->service->evaluate($user, $this->neighborhood, null, null);

        $this->assertSame(1.0, $r['weight']);
        $this->assertSame([], $r['flags']);
    }

    public function test_the_weight_never_goes_below_the_floor(): void
    {
        $user = User::factory()->unverified()->create(['created_at' => now()]);
        $this->reportFor($user, false, 6);

        $r = $this->service->evaluate($user, $this->neighborhood, 37.5, 10.18);

        $this->assertSame(0.05, $r['weight']);
    }
}






































































