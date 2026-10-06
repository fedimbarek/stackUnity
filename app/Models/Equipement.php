<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipement extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'type_equipement',
        'date_ajout',
        'image',
        'prix_louer',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
