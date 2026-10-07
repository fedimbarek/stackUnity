<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoolingPointRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'cooling_point_type_id' => 'required|exists:cooling_point_types,id',
            'name'                  => 'required|string|max:150',
            'latitude'              => 'required|numeric|between:-90,90',
            'longitude'             => 'required|numeric|between:-180,180',
            'address'               => 'nullable|string|max:255',
            'opening_hours'         => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'cooling_point_type_id.required' => 'Choisissez un type.',
            'cooling_point_type_id.exists'   => 'Type invalide.',
            'name.required'                  => 'Le nom est obligatoire.',
            'latitude.required'              => 'La latitude est obligatoire.',
            'longitude.required'             => 'La longitude est obligatoire.',
        ];
    }
}
