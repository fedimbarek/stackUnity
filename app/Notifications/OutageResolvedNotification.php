<?php

namespace App\Notifications;

use App\Models\PowerOutage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OutageResolvedNotification extends Notification
{
    use Queueable;

    public function __construct(public PowerOutage $outage) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Coupure résolue',
            'message' => "L'électricité est revenue dans ton quartier : ".$this->outage->neighborhood->name.'.',
            'outage_id' => $this->outage->id,
        ];
    }
}