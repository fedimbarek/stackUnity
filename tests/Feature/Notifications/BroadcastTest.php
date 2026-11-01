<?php

namespace Tests\Feature\Notifications;

use App\Jobs\SendBroadcastJob;
use App\Models\Neighborhood;
use App\Models\User;
use App\Notifications\BroadcastNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\InteractsWithRoles;
use Tests\TestCase;

class BroadcastTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private function payload(Neighborhood $n, array $override = []): array
    {
        return array_merge([
            'neighborhood_id' => $n->id,
            'title' => 'Coupure programmée',
            'message' => 'Une coupure aura lieu demain de 9h à 12h.',
        ], $override);
    }

    public function test_only_admins_can_use_the_broadcast_form(): void
    {
        $n = Neighborhood::factory()->create();

        $this->get(route('admin.notifications.broadcast.create'))->assertRedirect(route('login'));
        $this->actingAs($this->gestionnaire())->get(route('admin.notifications.broadcast.create'))->assertForbidden();
        $this->post(route('admin.notifications.broadcast.store'), $this->payload($n))->assertForbidden();
    }

    public function test_form_lists_the_neighborhoods(): void
    {
        $n = Neighborhood::factory()->create();

        $this->actingAs($this->admin())->get(route('admin.notifications.broadcast.create'))
            ->assertOk()
            ->assertViewIs('admin.notifications.broadcast')
            ->assertViewHas('neighborhoods', fn ($list) => $list->contains($n));
    }

    public function test_store_dispatches_the_job_and_reports_the_number_of_residents(): void
    {
        Queue::fake();
        $n = Neighborhood::factory()->create();
        $this->resident($n);
        $this->resident($n);
        $this->resident(Neighborhood::factory()->create());

        $this->actingAs($this->admin())->post(route('admin.notifications.broadcast.store'), $this->payload($n))
            ->assertRedirect(route('admin.notifications.broadcast.create'))
            ->assertSessionHas('status', 'Notification envoyée à 2 habitant(s) du quartier.');

        Queue::assertPushed(SendBroadcastJob::class, fn ($job) => $job->neighborhoodId === $n->id
            && $job->title === 'Coupure programmée'
            && str_contains($job->body, 'demain'));
    }

    public function test_store_validates_the_fields(): void
    {
        Queue::fake();
        $n = Neighborhood::factory()->create();
        $this->actingAs($this->admin());

        $this->post(route('admin.notifications.broadcast.store'), [])
            ->assertSessionHasErrors(['neighborhood_id' => 'Choisis un quartier.', 'title' => 'Le titre est obligatoire.', 'message' => 'Le message est obligatoire.']);
        $this->post(route('admin.notifications.broadcast.store'), $this->payload($n, ['neighborhood_id' => 9999]))
            ->assertSessionHasErrors(['neighborhood_id' => 'Ce quartier n\'existe pas.']);
        $this->post(route('admin.notifications.broadcast.store'), $this->payload($n, ['title' => 'ab', 'message' => 'court']))
            ->assertSessionHasErrors(['title', 'message']);
        $this->post(route('admin.notifications.broadcast.store'), $this->payload($n, ['title' => str_repeat('t', 81), 'message' => str_repeat('m', 501)]))
            ->assertSessionHasErrors(['title', 'message']);

        Queue::assertNothingPushed();
    }

    public function test_job_notifies_every_resident_of_the_neighborhood_and_nobody_else(): void
    {
        Notification::fake();
        $n = Neighborhood::factory()->create(['name' => 'Ennasr']);
        $inside = $this->resident($n);
        $outside = $this->resident(Neighborhood::factory()->create());

        (new SendBroadcastJob($n->id, 'Titre', 'Corps du message'))->handle();

        Notification::assertSentTo($inside, BroadcastNotification::class, fn ($notif) => $notif->neighborhood === 'Ennasr');
        Notification::assertNotSentTo($outside, BroadcastNotification::class);
    }

    public function test_job_does_nothing_when_the_neighborhood_no_longer_exists(): void
    {
        Notification::fake();
        $this->resident();

        (new SendBroadcastJob(9999, 'Titre', 'Corps du message'))->handle();

        Notification::assertNothingSent();
    }

    public function test_job_keeps_going_when_one_notification_fails(): void
    {
        $n = Neighborhood::factory()->create();
        $first = $this->resident($n);
        $second = $this->resident($n);
        // un canal mail impossible fait échouer le premier envoi, le second doit quand même partir en base
        \Illuminate\Support\Facades\Mail::shouldReceive('mailer')->andThrow(new \RuntimeException('smtp down'));
        $first->preferences()->update(['via_database' => false, 'via_mail' => true]);
        $second->preferences()->update(['via_database' => true, 'via_mail' => false]);

        (new SendBroadcastJob($n->id, 'Titre', 'Corps du message'))->handle();

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $second->id]);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $first->id]);
    }

    public function test_notification_content_respects_the_channels_and_ignores_the_critical_filter(): void
    {
        $n = Neighborhood::factory()->create(['name' => 'Ennasr']);
        $user = $this->resident($n, ['name' => 'Sami']);
        $user->preferences()->update(['only_critical' => true, 'via_mail' => false]);
        $notification = new BroadcastNotification('Titre', 'Corps du message', 'Ennasr');

        $this->assertSame(['database'], $notification->via($user));
        $this->assertSame('info', $notification->toArray($user)['level']);
        $this->assertSame('Titre — Ennasr', $notification->toArray($user)['title']);

        $mail = $notification->toMail($user);
        $this->assertSame('Titre — Ennasr', $mail->subject);
        $this->assertSame('Bonjour Sami,', $mail->greeting);
        $this->assertContains('Corps du message', $mail->introLines);
    }

    public function test_job_stores_a_readable_database_notification(): void
    {
        $n = Neighborhood::factory()->create(['name' => 'Ennasr']);
        $user = $this->resident($n);

        SendBroadcastJob::dispatchSync($n->id, 'Eau coupée', 'Travaux sur le réseau de distribution.');

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Eau coupée — Ennasr')
            ->assertSee('Travaux sur le réseau de distribution.');
    }
}
