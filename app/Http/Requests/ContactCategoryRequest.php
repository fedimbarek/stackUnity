<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // en modification, on ignore la catégorie courante pour l'unicité
        $current = $this->route('contact_category');

        return [
            'name'  => ['required', 'string', 'min:3', 'max:100',
                        Rule::unique('contact_categories', 'name')->ignore($current?->id)],
            // 'icon'  => ['required', 'string', Rule::in(self::icons())],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la catégorie est obligatoire.',
            'name.unique'   => 'Cette catégorie existe déjà.',
            'name.min'      => 'Le nom doit contenir au moins 3 caractères.',
            'icon.in'       => 'Icône invalide.',
            'color.regex'   => 'La couleur doit être au format hexadécimal (#RRGGBB).',
        ];
    }

    public static function icons(): array
    {
        return [
            'bi-telephone', 'bi-hospital', 'bi-shield-check', 'bi-lightning-charge',
            'bi-capsule', 'bi-fire', 'bi-droplet', 'bi-heart-pulse',
        ];
    }
}
