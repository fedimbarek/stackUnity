<?php

namespace App\Listeners;

use App\Events\OutageConfirmed;
use App\Notifications\OutageConfirmedNotification;
use Illuminate\Support\Facades\Notification;

class SendOutageConfirmedNotification
{
    public function handle(OutageConfirmed $event): void
    {
        $outage = $event->outage->load('neighborhood.users');

        Notification::send($outage->neighborhood->users, new OutageConfirmedNotification($outage));
    }
}