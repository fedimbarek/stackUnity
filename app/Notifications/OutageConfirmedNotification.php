<?php

namespace App\Notifications;

use App\Models\PowerOutage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OutageConfirmedNotification extends Notification
{
    use Queueable;

    public function __construct(public PowerOutage $outage) {}

    public function via(object $notifiable): array
    {
        return ['database']; // Personne 2 ajoutera ici FcmChannel::class une fois son module prêt
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Coupure confirmée',
            'message' => 'Une coupure de courant a été confirmée dans ton quartier : '.$this->outage->neighborhood->name.'.',
            'outage_id' => $this->outage->id,
        ];
    }
}