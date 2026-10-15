<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class NotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // chaque utilisateur modifie ses propres préférences
    }

    public function rules(): array
    {
        return [
            'via_database' => ['sometimes', 'boolean'],
            'via_mail' => ['sometimes', 'boolean'],
            'only_critical' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (! $this->boolean('via_database') && ! $this->boolean('via_mail')) {
                $v->errors()->add('via_database', 'Choisis au moins un canal de notification.');
            }
        });
    }
}