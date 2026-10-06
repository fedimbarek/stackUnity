<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ReportNeighborhoodScope implements Scope
{
    /**
     * Un "gestionnaire" ne voit que les rapports de son quartier (ou globaux, sans quartier).
     * Un "admin" (super-admin) voit tout — aucun filtre appliqué.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return; // super-admin : pas de restriction
        }

        if ($user->hasRole('gestionnaire')) {
            $builder->where(function ($q) use ($user) {
                $q->where('neighborhood_id', $user->neighborhood_id)
                  ->orWhereNull('neighborhood_id');
            });
        }
    }
}