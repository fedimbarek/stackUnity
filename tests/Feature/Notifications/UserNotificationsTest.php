<?php

namespace Tests\Feature\Notifications;

use App\Models\Neighborhood;
use App\Models\User;
use App\Models\WeatherAlert;
use App\Models\NotificationPreference;
use App\Notifications\HeatAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_weather_alert_is_saved_and_visible_in_notification_list(): void
    {
        // 1. Créer un quartier
        $neighborhood = Neighborhood::factory()->create();

        // 2. Créer un utilisateur
        $user = User::factory()->create([
            'neighborhood_id' => $neighborhood->id,
        ]);

        // 3. Créer directement les préférences
        NotificationPreference::create([
            'user_id' => $user->id,
            'via_database' => true,
            'via_mail' => false,
            'only_critical' => false,
        ]);

        // 4. Créer une alerte canicule
        $alert = WeatherAlert::create([
            'neighborhood_id' => $neighborhood->id,
            'level' => 'canicule',
            'source' => 'manual',
            'message' => 'Hydratez-vous et restez au frais.',
        ]);

        // 5. Envoyer une notification uniquement en base
        // Sans appeler User::preferences()
        $notification = new class($alert) extends HeatAlertNotification {
            public function via(object $notifiable): array
            {
                return ['database'];
            }
        };

        Notification::sendNow($user, $notification);

        // 6. Vérifier les préférences
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'via_database' => true,
        ]);

        // 7. Vérifier la notification enregistrée
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
        ]);

        // 8. Vérifier l'affichage dans l'historique
        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Alerte canicule')
            ->assertSee('Hydratez-vous et restez au frais.');
    }
}
