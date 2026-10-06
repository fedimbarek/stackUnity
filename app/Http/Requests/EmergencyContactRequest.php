<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmergencyContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // le middleware role:admin protège déjà les routes
    }

    public function rules(): array
    {
        return [
            'contact_category_id' => ['required', 'integer', 'exists:contact_categories,id'],
            'name'        => ['required', 'string', 'min:3', 'max:255'],
            'phone'       => ['required', 'regex:/^\+?[0-9 ]{3,15}$/'],
            'address'     => ['nullable', 'string', 'max:255'],
            'city'        => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_24h'      => ['nullable', 'boolean'],
            'is_priority' => ['nullable', 'boolean'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'contact_category_id.required' => 'Veuillez choisir une catégorie.',
            'contact_category_id.exists'   => 'La catégorie choisie est invalide.',
            'name.required'  => 'Le nom du contact est obligatoire.',
            'name.min'       => 'Le nom doit contenir au moins 3 caractères.',
            'phone.required' => 'Le numéro de téléphone est obligatoire.',
            'phone.regex'    => 'Numéro invalide (3 à 15 chiffres, "+" et espaces autorisés).',
            'description.max'=> 'La description ne doit pas dépasser 1000 caractères.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom', 'phone' => 'téléphone', 'address' => 'adresse',
            'city' => 'ville', 'description' => 'description',
        ];
    }
}
