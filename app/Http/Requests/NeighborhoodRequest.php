<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NeighborhoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $neighborhoodId = $this->route('neighborhood')?->id;

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('neighborhoods', 'name')->ignore($neighborhoodId)],
            'city' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => "Le nom du quartier est obligatoire.",
            'name.unique' => "Ce quartier existe déjà.",
            'name.max' => "Le nom du quartier est trop long.",
            'city.required' => "La ville est obligatoire.",
            'latitude.numeric' => "La latitude doit être un nombre (sélectionne un point sur la carte).",
            'latitude.between' => "La latitude doit être comprise entre -90 et 90.",
            'longitude.numeric' => "La longitude doit être un nombre (sélectionne un point sur la carte).",
            'longitude.between' => "La longitude doit être comprise entre -180 et 180.",
        ];
    }
}