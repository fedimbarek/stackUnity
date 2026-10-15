<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WeatherAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // routes protégées par role:admin
    }

    public function rules(): array
    {
        return [
            'neighborhood_id' => ['required', 'exists:neighborhoods,id'],
            'level' => ['required', Rule::in(['warning', 'canicule'])],
            'message' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'neighborhood_id.required' => 'Choisis un quartier.',
            'level.in' => 'Niveau invalide.',
            'message.required' => 'Le message est obligatoire.',
            'message.min' => 'Le message doit contenir au moins 10 caractères.',
            'message.max' => 'Le message ne doit pas dépasser 500 caractères.',
        ];
    }
}