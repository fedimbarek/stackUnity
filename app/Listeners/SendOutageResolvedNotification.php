<?php

namespace App\Listeners;

use App\Events\OutageResolved;
use App\Notifications\OutageResolvedNotification;
use Illuminate\Support\Facades\Notification;

class SendOutageResolvedNotification
{
    public function handle(OutageResolved $event): void
    {
        $outage = $event->outage->load('neighborhood.users');

        Notification::send($outage->neighborhood->users, new OutageResolvedNotification($outage));
    }
}