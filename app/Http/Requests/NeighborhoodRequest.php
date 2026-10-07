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

    // Nettoie les espaces avant la validation ("  ab " compte 2 caractères, pas 4)
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->name) ? trim($this->name) : $this->name,
            'city' => is_string($this->city) ? trim($this->city) : $this->city,
        ]);
    }

    public function rules(): array
    {
        $neighborhoodId = $this->route('neighborhood')?->id;

        return [
            'name' => [
                'required', 'string', 'min:3', 'max:255',
                Rule::unique('neighborhoods', 'name')->ignore($neighborhoodId),
            ],
            'city' => ['required', 'string', 'min:3', 'max:255'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du quartier est obligatoire.',
            'name.string' => 'Le nom du quartier doit être un texte.',
            'name.min' => 'Le nom du quartier doit contenir au moins 3 caractères.',
            'name.max' => 'Le nom du quartier est trop long (255 caractères maximum).',
            'name.unique' => 'Ce quartier existe déjà.',

            'city.required' => 'La ville est obligatoire.',
            'city.string' => 'La ville doit être un texte.',
            'city.min' => 'La ville doit contenir au moins 3 caractères.',
            'city.max' => 'Le nom de la ville est trop long (255 caractères maximum).',

            'latitude.required_with' => 'La latitude est manquante : sélectionne un point sur la carte.',
            'latitude.numeric' => 'La latitude doit être un nombre (sélectionne un point sur la carte).',
            'latitude.between' => 'La latitude doit être comprise entre -90 et 90.',

            'longitude.required_with' => 'La longitude est manquante : sélectionne un point sur la carte.',
            'longitude.numeric' => 'La longitude doit être un nombre (sélectionne un point sur la carte).',
            'longitude.between' => 'La longitude doit être comprise entre -180 et 180.',
        ];
    }
}