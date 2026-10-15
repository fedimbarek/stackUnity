<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'via_database', 'via_mail', 'only_critical'];

    /** Valeurs par défaut pour un utilisateur qui n'a encore rien choisi. */
    protected $attributes = [
        'via_database' => true,
        'via_mail' => true,
        'only_critical' => false,
    ];

    protected function casts(): array
    {
        return [
            'via_database' => 'boolean',
            'via_mail' => 'boolean',
            'only_critical' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Canaux à utiliser pour une notification de ce niveau.
     * $filterByLevel = false : on ignore le filtre « seulement les alertes critiques »
     * (cas d'une diffusion manuelle de l'admin).
     */
    public function channelsFor(string $level, bool $filterByLevel = true): array
    {
        if ($filterByLevel && $this->only_critical && $level !== 'canicule') {
            return [];
        }

        $channels = [];

        if ($this->via_database) {
            $channels[] = 'database';
        }
        if ($this->via_mail) {
            $channels[] = 'mail';
        }

        return $channels;
    }
}