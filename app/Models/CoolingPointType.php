<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CoolingPointType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'color',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function ($type) {
            if (empty($type->slug)) {
                $type->slug = Str::slug($type->name);
            }
        });
    }

    // Relation 1-N : un type a plusieurs points
    public function coolingPoints()
    {
        return $this->hasMany(CoolingPoint::class);
    }
}
