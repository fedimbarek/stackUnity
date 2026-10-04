<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OutageRiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weather_forecast_id' => ['required', 'exists:weather_forecasts,id'],
            'risk_level' => ['required', Rule::in(['faible', 'moyen', 'eleve'])],
            'description' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'weather_forecast_id.required' => 'Choisis une prévision.',
            'weather_forecast_id.exists' => 'Cette prévision n\'existe pas.',
            'risk_level.in' => 'Niveau de risque invalide.',
            'description.required' => 'La description est obligatoire.',
            'description.min' => 'La description doit contenir au moins 10 caractères.',
            'description.max' => 'La description ne doit pas dépasser 500 caractères.',
        ];
    }
}