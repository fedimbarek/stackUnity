<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\WeatherAlert;
use App\Notifications\HeatAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public WeatherAlert $alert)
    {
    }

    public function handle(): void
    {
        $users = User::where('neighborhood_id', $this->alert->neighborhood_id)->get();

        foreach ($users as $user) {
            try {
                $user->notify(new HeatAlertNotification($this->alert));
            } catch (\Throwable $e) {
                // Un envoi qui échoue ne doit pas bloquer les autres habitants.
                report($e);
            }
        }
    }
}