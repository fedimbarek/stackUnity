<?php

namespace Tests\Feature\Api;

use App\Jobs\SendBroadcastJob;
use App\Models\Neighborhood;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function notify(User $user, array $data = [], bool $read = false)
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => $data + ['title' => 'Titre', 'message' => 'Message', 'level' => 'canicule'],
            'read_at' => $read ? now() : null,
        ]);
    }

    public function test_guest_gets_a_json_401(): void
    {
        $this->getJson(route('api.notifications.index'))->assertUnauthorized();
        $this->getJson(route('api.notifications.preferences'))->assertUnauthorized();
    }

    public function test_index_lists_my_notifications_with_the_unread_count(): void
    {
        $me = $this->resident();
        $this->notify($me, ['title' => 'Alerte canicule']);
        $this->notify($me, [], true);
        $this->notify($this->resident());

        $this->actingAs($me)->getJson(route('api.notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['unread_count', 'data' => [['id', 'title', 'message', 'level', 'read', 'created_at']]]);
    }

    public function test_index_tolerates_notifications_without_data_fields(): void
    {
        $me = $this->resident();
        $me->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'test', 'data' => []]);

        $this->actingAs($me)->getJson(route('api.notifications.index'))
            ->assertOk()
            ->assertJsonPath('data.0.title', null)
            ->assertJsonPath('data.0.read', false);
    }

    public function test_read_marks_my_notification_as_read(): void
    {
        $me = $this->resident();
        $n = $this->notify($me);

        $this->actingAs($me)->putJson(route('api.notifications.read', $n->id))
            ->assertOk()->assertJsonPath('message', 'Notification marquée comme lue.');

        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_read_refuses_the_notification_of_someone_else_or_unknown(): void
    {
        $n = $this->notify($this->resident());

        $this->actingAs($this->resident())->putJson(route('api.notifications.read', $n->id))->assertNotFound();
        $this->actingAs($this->resident())->putJson(route('api.notifications.read', 'unknown'))->assertNotFound();
        $this->assertNull($n->fresh()->read_at);
    }

    public function test_preferences_default_to_database_and_mail(): void
    {
        $this->actingAs($this->resident())->getJson(route('api.notifications.preferences'))
            ->assertOk()
            ->assertExactJson(['data' => ['via_database' => true, 'via_mail' => true, 'only_critical' => false]]);
    }

    public function test_preferences_can_be_updated(): void
    {
        $me = $this->resident();

        $this->actingAs($me)->putJson(route('api.notifications.preferences.update'), [
            'via_database' => true, 'via_mail' => false, 'only_critical' => true,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Préférences enregistrées.')
            ->assertJsonPath('data', ['via_database' => true, 'via_mail' => false, 'only_critical' => true]);

        $this->assertDatabaseHas('notification_preferences', ['user_id' => $me->id, 'via_mail' => false, 'only_critical' => true]);
    }

    public function test_preferences_update_requires_at_least_one_channel(): void
    {
        $this->actingAs($this->resident())
            ->putJson(route('api.notifications.preferences.update'), ['via_database' => false, 'via_mail' => false])
            ->assertStatus(422)->assertJsonValidationErrors('via_database');
    }

    public function test_admin_can_broadcast_to_a_neighborhood(): void
    {
        Queue::fake();
        $n = Neighborhood::factory()->create();
        $this->resident($n);
        $this->resident($n);
        $this->resident(Neighborhood::factory()->create());

        $this->actingAs($this->admin())->postJson(route('api.notifications.broadcast'), [
            'neighborhood_id' => $n->id, 'title' => 'Coupure prévue', 'message' => 'Travaux ce soir de 20h à 22h.',
        ])
            ->assertStatus(202)
            ->assertJsonPath('recipients', 2)
            ->assertJsonPath('message', "Notification en cours d'envoi.");

        Queue::assertPushed(SendBroadcastJob::class);
    }

    public function test_broadcast_is_validated_and_reserved_to_admins(): void
    {
        Queue::fake();
        $n = Neighborhood::factory()->create();
        $payload = ['neighborhood_id' => $n->id, 'title' => 'Coupure prévue', 'message' => 'Travaux ce soir de 20h à 22h.'];

        $this->actingAs($this->admin())->postJson(route('api.notifications.broadcast'), [])
            ->assertStatus(422)->assertJsonValidationErrors(['neighborhood_id', 'title', 'message']);
        $this->actingAs($this->gestionnaire())->postJson(route('api.notifications.broadcast'), $payload)->assertForbidden();
        $this->actingAs($this->resident())->postJson(route('api.notifications.broadcast'), $payload)->assertForbidden();

        Queue::assertNothingPushed();
    }
}
