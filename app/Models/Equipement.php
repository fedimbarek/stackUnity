<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipement extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'type_equipement',
        'date_ajout',
        'image',
        'prix_louer',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_ajout' => 'date',
        ];
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
