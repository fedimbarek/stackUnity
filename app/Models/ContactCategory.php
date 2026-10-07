<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'icon', 'color'];

    /** Une catégorie possède plusieurs contacts (1-N). */
    public function contacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }
}
