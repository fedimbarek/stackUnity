<?php

namespace Tests\Feature\Notifications;

use App\Jobs\SendNotificationJob;
use App\Models\Neighborhood;
use App\Models\User;
use App\Models\WeatherAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_weather_alert_is_saved_and_visible_in_notification_list(): void
    {
        $neighborhood = Neighborhood::factory()->create();
        $user = User::factory()->create([
            'neighborhood_id' => $neighborhood->id,
        ]);
        $alert = WeatherAlert::create([
            'neighborhood_id' => $neighborhood->id,
            'level' => 'canicule',
            'source' => 'manual',
            'message' => 'Hydratez-vous et restez au frais.',
        ]);

        SendNotificationJob::dispatchSync($alert);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'via_database' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Alerte canicule')
            ->assertSee('Hydratez-vous et restez au frais.');
    }
}
