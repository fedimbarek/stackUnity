<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoolingPointTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('cooling_point_type')?->id;

        return [
            'name'        => 'required|string|max:100|unique:cooling_point_types,name,' . $id,
            'icon'        => 'required|string|max:50',
            'color'       => 'required|string|max:20',
            'description' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'Le nom du type est obligatoire.',
            'name.unique'    => 'Ce nom existe déjà.',
            'icon.required'  => 'L\'icône est obligatoire.',
            'color.required' => 'La couleur est obligatoire.',
        ];
    }
}
