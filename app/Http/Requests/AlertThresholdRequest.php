<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AlertThresholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // routes protégées par role:admin
    }

    public function rules(): array
    {
        return [
            'warning' => ['required', 'numeric', 'between:10,60'],
            'canicule' => ['required', 'numeric', 'between:10,60', 'gt:warning'],
        ];
    }

    public function messages(): array
    {
        return [
            'warning.required' => 'Le seuil de forte chaleur est obligatoire.',
            'warning.between' => 'Le seuil doit être compris entre 10 et 60 °C.',
            'canicule.required' => 'Le seuil de canicule est obligatoire.',
            'canicule.between' => 'Le seuil doit être compris entre 10 et 60 °C.',
            'canicule.gt' => 'Le seuil de canicule doit être supérieur au seuil de forte chaleur.',
        ];
    }
}