<?php

namespace App\Services;

use App\Models\Equipement;
use Illuminate\Database\Eloquent\Collection;

class EquipementService
{
    public function getAll(): Collection
    {
        return Equipement::latest('date_ajout')->get();
    }

    public function find(int $id): Equipement
    {
        return Equipement::findOrFail($id);
    }

    public function create(array $data): Equipement
    {
        return Equipement::create($data);
    }

    public function update(Equipement $equipement, array $data): Equipement
    {
        $equipement->update($data);

        return $equipement->refresh();
    }

    public function delete(Equipement $equipement): bool
    {
        return $equipement->delete();
    }
}