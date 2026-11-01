<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendNotificationJob;
use App\Models\Neighborhood;
use App\Models\User;
use App\Models\WeatherAlert;
use App\Notifications\HeatAlertNotification;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    private function alert(Neighborhood $n): WeatherAlert
    {
        return WeatherAlert::factory()->create(['neighborhood_id' => $n->id, 'level' => 'canicule']);
    }

    public function test_it_notifies_the_residents_of_the_neighborhood_only(): void
    {
        Notification::fake();
        $n = Neighborhood::factory()->create();
        $inside = User::factory()->count(2)->create(['neighborhood_id' => $n->id]);
        $outside = User::factory()->create(['neighborhood_id' => Neighborhood::factory()->create()->id]);

        (new SendNotificationJob($this->alert($n)))->handle();

        Notification::assertSentTo($inside, HeatAlertNotification::class);
        Notification::assertNotSentTo($outside, HeatAlertNotification::class);
    }

    public function test_one_failing_notification_does_not_block_the_others(): void
    {
        Exceptions::fake();
        $n = Neighborhood::factory()->create();
        User::factory()->count(3)->create(['neighborhood_id' => $n->id]);

        $calls = 0;
        $this->mock(Dispatcher::class, function ($mock) use (&$calls) {
            $mock->shouldReceive('send')->times(3)->andReturnUsing(function () use (&$calls) {
                if (++$calls === 1) {
                    throw new \RuntimeException('smtp down');
                }
            });
        });

        (new SendNotificationJob($this->alert($n)))->handle();

        $this->assertSame(3, $calls);
        Exceptions::assertReported(fn (\RuntimeException $e) => $e->getMessage() === 'smtp down');
    }

    public function test_the_job_retries_three_times(): void
    {
        $n = Neighborhood::factory()->create();

        $this->assertSame(3, (new SendNotificationJob($this->alert($n)))->tries);
    }
}
