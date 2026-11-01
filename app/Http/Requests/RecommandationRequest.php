<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecommandationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $besoin = $this->input('besoin');

        if (is_string($besoin)) {
            $this->merge(['besoin' => trim(strip_tags($besoin))]);
        }
    }

    public function rules(): array
    {
        return [
            'besoin' => ['required', 'string', 'min:5', 'max:500'],
            'lang' => ['sometimes', 'string', 'in:fr,en,ar,es'],
        ];
    }

    public function messages(): array
    {
        return [
            'besoin.required' => 'Décrivez votre besoin pour obtenir une recommandation.',
            'besoin.min' => 'Votre description doit contenir au moins 5 caractères.',
            'besoin.max' => 'Votre description ne peut pas dépasser 500 caractères.',
        ];
    }
}
