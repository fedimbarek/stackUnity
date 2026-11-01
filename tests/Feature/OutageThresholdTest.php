<?php

namespace Tests\Feature;

use App\Models\Neighborhood;
use App\Models\User;
use App\Services\OutageAggregationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OutageThresholdTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Utilisateur "de confiance" : email vérifié (défaut de la factory) et compte
     * créé il y a plus d'1h. Sinon ReporterTrustService applique x0.7
     * ("compte_tres_recent") et 3 signalements font 2.1 < 3.0 (seuil de confirmation).
     */
    private function trustedUser(Neighborhood $neighborhood): User
    {
        return User::factory()->create([
            'neighborhood_id' => $neighborhood->id,
            'created_at' => now()->subDays(2),
        ]);
    }

    public function test_outage_turns_red_after_3_distinct_users(): void
    {
        Notification::fake();

        $service = app(OutageAggregationService::class);
        $neighborhood = Neighborhood::factory()->create();

        // 2 users (poids 1.0 chacun = 2.0 < 3.0) => reste "reported" (jaune)
        foreach (range(1, 2) as $i) {
            $outage = $service->report($this->trustedUser($neighborhood), $neighborhood, null, null);
            $this->assertSame('reported', $outage->status);
        }

        // 3eme user (somme = 3.0) => "confirmed" (rouge)
        $outage = $service->report($this->trustedUser($neighborhood), $neighborhood, null, null);

        $this->assertSame('confirmed', $outage->status);
        $this->assertNotNull($outage->confirmed_at);
    }

    public function test_same_user_reporting_5_times_does_not_confirm(): void
    {
        Notification::fake();

        $service = app(OutageAggregationService::class);
        $neighborhood = Neighborhood::factory()->create();
        $user = $this->trustedUser($neighborhood);

        foreach (range(1, 5) as $i) {
            $outage = $service->report($user, $neighborhood, null, null);
        }

        // Un même utilisateur ne compte qu'une fois (son meilleur poids = 1.0)
        $this->assertSame('reported', $outage->status);
    }

    public function test_new_accounts_have_reduced_weight_and_do_not_confirm(): void
    {
        Notification::fake();

        $service = app(OutageAggregationService::class);
        $neighborhood = Neighborhood::factory()->create();

        // 3 comptes créés à l'instant : 3 x 0.7 = 2.1 < 3.0 => anti-abus : pas de confirmation
        foreach (range(1, 3) as $i) {
            $user = User::factory()->create(['neighborhood_id' => $neighborhood->id]);
            $outage = $service->report($user, $neighborhood, null, null);
        }

        $this->assertSame('reported', $outage->status);
    }
}