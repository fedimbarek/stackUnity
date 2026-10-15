<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WeatherForecastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // l'accès est déjà protégé par le middleware role:admin|gestionnaire
    }

    public function rules(): array
    {
        return [
            'neighborhood_id' => ['required', 'exists:neighborhoods,id'],
            'date' => [
                'required',
                'date',
                Rule::unique('weather_forecasts', 'date')
                    ->where('neighborhood_id', $this->input('neighborhood_id'))
                    ->ignore($this->route('forecast')),
            ],
            'temp_max' => ['required', 'numeric', 'between:-10,60'],
            'temp_min' => ['required', 'numeric', 'between:-10,60', 'lte:temp_max'],
            'level' => ['required', Rule::in(['normal', 'warning', 'canicule'])],
        ];
    }

    public function messages(): array
    {
        return [
            'neighborhood_id.required' => 'Choisis un quartier.',
            'neighborhood_id.exists' => 'Ce quartier n\'existe pas.',
            'date.required' => 'La date est obligatoire.',
            'date.unique' => 'Une prévision existe déjà pour ce quartier à cette date.',
            'temp_max.required' => 'La température maximale est obligatoire.',
            'temp_max.between' => 'La température doit être comprise entre -10 et 60 °C.',
            'temp_min.required' => 'La température minimale est obligatoire.',
            'temp_min.between' => 'La température doit être comprise entre -10 et 60 °C.',
            'temp_min.lte' => 'La température minimale doit être inférieure ou égale à la maximale.',
            'level.in' => 'Niveau invalide.',
        ];
    }
}