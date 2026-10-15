<?php

namespace App\Notifications;

use App\Models\WeatherAlert;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HeatAlertNotification extends Notification
{
    public function __construct(public WeatherAlert $alert)
    {
        $this->alert->loadMissing('neighborhood');
    }

    /** Canaux choisis par l'utilisateur dans ses préférences. */
    public function via(object $notifiable): array
    {
        return $notifiable->preferences()->channelsFor($this->alert->level);
    }

    private function title(): string
    {
        $label = $this->alert->level === 'canicule' ? 'Alerte canicule' : 'Alerte forte chaleur';

        return $label . ' — ' . $this->alert->neighborhood->name;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line($this->alert->message)
            ->action('Voir les prévisions', route('weather.index'))
            ->line('HeatAlert — restez en sécurité.');

        if ($this->alert->level === 'canicule') {
            $mail->error();
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alert_id' => $this->alert->id,
            'level' => $this->alert->level,
            'title' => $this->title(),
            'message' => $this->alert->message,
            'neighborhood' => $this->alert->neighborhood->name,
        ];
    }
}