<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EquipementTranslationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lang' => ['required', 'string', 'in:fr,en,ar,es'],
            'ids' => ['sometimes', 'array', 'max:50'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:equipements,id'],
        ];
    }
}
