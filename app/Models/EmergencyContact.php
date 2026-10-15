<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_category_id', 'name', 'phone', 'address', 'city',
        'description', 'is_24h', 'is_priority', 'is_active',
    ];

    protected $casts = [
        'is_24h'      => 'boolean',
        'is_priority' => 'boolean',
        'is_active'   => 'boolean',
    ];

    /** Un contact appartient à une catégorie (N-1). */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ContactCategory::class, 'contact_category_id');
    }

    /** Numéro nettoyé pour le lien tel: */
    public function getPhoneLinkAttribute(): string
    {
        return preg_replace('/[^0-9+]/', '', $this->phone);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function ($q) use ($term) {
            $q->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                  ->orWhere('city', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%");
            });
        });
    }

    public function scopeOfCategory(Builder $query, $categoryId): Builder
    {
        return $query->when($categoryId, fn ($q) => $q->where('contact_category_id', $categoryId));
    }
}
