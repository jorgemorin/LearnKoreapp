<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar la ingesta de vocabulario.
 * Ruta: POST /api/vocabulary
 */
class StoreVocabularyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Solo usuarios autenticados (garantizado por el middleware auth:sanctum)
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:255', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'El texto en coreano es obligatorio.',
            'text.string'   => 'El texto debe ser una cadena de caracteres.',
            'text.max'      => 'El texto no puede superar los 255 caracteres.',
            'text.min'      => 'El texto no puede estar vacío.',
        ];
    }
}
