<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Neighborhood extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'latitude',
        'longitude',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function powerOutages()
{
    return $this->hasMany(PowerOutage::class);
}
}