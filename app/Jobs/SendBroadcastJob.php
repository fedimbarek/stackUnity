<?php

namespace App\Jobs;

use App\Models\Neighborhood;
use App\Models\User;
use App\Notifications\BroadcastNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBroadcastJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $neighborhoodId,
        public string $title,
        public string $body,
    ) {
    }

    public function handle(): void
    {
        $neighborhood = Neighborhood::find($this->neighborhoodId);

        if (! $neighborhood) {
            return;
        }

        foreach (User::where('neighborhood_id', $neighborhood->id)->get() as $user) {
            try {
                $user->notify(new BroadcastNotification($this->title, $this->body, $neighborhood->name));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}