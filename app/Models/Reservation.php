<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'numero',
        'date_debut',
        'date_fin',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'confirmee_at' => 'datetime',
        ];
    }

    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class);
    }
}
