<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'neighborhood_id' => ['nullable', 'exists:neighborhoods,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => "Le titre du rapport est obligatoire.",
            'period_start.required' => "La date de début est obligatoire.",
            'period_start.date' => "La date de début n'est pas valide.",
            'period_end.required' => "La date de fin est obligatoire.",
            'period_end.after_or_equal' => "La date de fin doit être après la date de début.",
            'neighborhood_id.exists' => "Le quartier sélectionné n'existe pas.",
        ];
    }
}