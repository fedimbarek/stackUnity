<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    private function notify(User $user, string $title = 'Titre', bool $read = false): DatabaseNotification
    {
        return DatabaseNotification::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Notifications\\BroadcastNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => ['level' => 'info', 'title' => $title, 'message' => 'Message de test'],
            'read_at' => $read ? now() : null,
        ]);
    }

    // ---------------------------------------------------------------- liste / lecture

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $this->put(route('notifications.readAll'))->assertRedirect(route('login'));
        $this->get(route('notifications.preferences.edit'))->assertRedirect(route('login'));
    }

    public function test_index_only_lists_the_notifications_of_the_user_and_paginates(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        foreach (range(1, 12) as $i) {
            $this->notify($user, "Mienne {$i}");
        }
        $this->notify($other, 'Celle des autres');

        $response = $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertViewIs('notifications.index')
            ->assertDontSee('Celle des autres');

        $this->assertCount(10, $response->viewData('notifications'));
        $this->assertSame(12, $response->viewData('notifications')->total());
    }

    public function test_a_notification_can_be_marked_as_read(): void
    {
        $user = User::factory()->create();
        $notification = $this->notify($user);

        $this->actingAs($user)->from(route('notifications.index'))
            ->put(route('notifications.read', $notification->id))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_the_read_route_also_accepts_patch_and_post(): void
    {
        $user = User::factory()->create();
        $a = $this->notify($user);
        $b = $this->notify($user);
        $this->actingAs($user);

        $this->patch(route('notifications.read', $a->id))->assertRedirect();
        $this->post(route('notifications.read', $b->id))->assertRedirect();

        $this->assertNotNull($a->fresh()->read_at);
        $this->assertNotNull($b->fresh()->read_at);
    }

    public function test_a_user_cannot_read_the_notification_of_someone_else(): void
    {
        $owner = User::factory()->create();
        $notification = $this->notify($owner);

        $this->actingAs(User::factory()->create())
            ->put(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_read_all_marks_every_unread_notification_of_the_user_only(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $a = $this->notify($user);
        $b = $this->notify($user);
        $foreign = $this->notify($other);

        $this->actingAs($user)->put(route('notifications.readAll'))
            ->assertRedirect()
            ->assertSessionHas('status', 'Toutes les notifications sont marquées comme lues.');

        $this->assertNotNull($a->fresh()->read_at);
        $this->assertNotNull($b->fresh()->read_at);
        $this->assertNull($foreign->fresh()->read_at);
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    // ---------------------------------------------------------------- préférences

    public function test_preferences_page_creates_default_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('notifications.preferences.edit'))
            ->assertOk()
            ->assertViewIs('notifications.preferences')
            ->assertViewHas('prefs', fn ($p) => $p->via_database && $p->via_mail && ! $p->only_critical);

        $this->assertDatabaseCount('notification_preferences', 1);
    }

    public function test_preferences_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('notifications.preferences.update'), ['via_database' => 1, 'only_critical' => 1])
            ->assertRedirect(route('notifications.preferences.edit'))
            ->assertSessionHas('status', 'Préférences enregistrées.');

        $prefs = $user->preferences()->fresh();
        $this->assertTrue($prefs->via_database);
        $this->assertFalse($prefs->via_mail);
        $this->assertTrue($prefs->only_critical);
        $this->assertDatabaseCount('notification_preferences', 1);
    }

    public function test_at_least_one_channel_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('notifications.preferences.update'), ['only_critical' => 1])
            ->assertSessionHasErrors(['via_database' => 'Choisis au moins un canal de notification.']);
        $this->put(route('notifications.preferences.update'), ['via_database' => 'oui'])
            ->assertSessionHasErrors('via_database');
    }

    // ---------------------------------------------------------------- modèle

    public function test_channels_depend_on_the_preferences_and_the_level(): void
    {
        $all = new NotificationPreference();
        $this->assertSame(['database', 'mail'], $all->channelsFor('warning'));

        $mailOnly = new NotificationPreference(['via_database' => false, 'via_mail' => true]);
        $this->assertSame(['mail'], $mailOnly->channelsFor('canicule'));

        $none = new NotificationPreference(['via_database' => false, 'via_mail' => false]);
        $this->assertSame([], $none->channelsFor('canicule'));

        $critical = new NotificationPreference(['only_critical' => true]);
        $this->assertSame([], $critical->channelsFor('warning'));
        $this->assertSame(['database', 'mail'], $critical->channelsFor('canicule'));
        // une diffusion manuelle de l'admin ignore le filtre « critique seulement »
        $this->assertSame(['database', 'mail'], $critical->channelsFor('info', false));
    }

    public function test_preference_belongs_to_a_user(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->preferences()->user->is($user));
        $this->assertSame($user->preferences()->id, $user->preferences()->id); // firstOrCreate : pas de doublon
    }
}
