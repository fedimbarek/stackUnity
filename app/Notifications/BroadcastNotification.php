<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BroadcastNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $body,
        public string $neighborhood,
    ) {
    }

    /** L'admin a choisi d'envoyer : on respecte les canaux, mais pas le filtre « critique seulement ». */
    public function via(object $notifiable): array
    {
        return $notifiable->preferences()->channelsFor('info', false);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title . ' — ' . $this->neighborhood)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line($this->body)
            ->line('HeatAlert — message de votre quartier.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'level' => 'info',
            'title' => $this->title . ' — ' . $this->neighborhood,
            'message' => $this->body,
            'neighborhood' => $this->neighborhood,
        ];
    }
}