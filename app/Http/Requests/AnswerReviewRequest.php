<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar la respuesta a una tarjeta de repaso.
 * Ruta: POST /api/review/{progressId}/answer
 *
 * Acepta dos formatos:
 *   - Nuevo (Anki): { "rating": "good", "time_taken_ms": 1500 }
 *   - Legacy:       { "is_correct": true, "time_taken_ms": 1500 }
 */
class AnswerReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rating'        => ['sometimes', 'string', 'in:again,hard,good,easy'],
            'is_correct'    => ['sometimes', 'boolean'],
            'time_taken_ms' => ['required', 'integer', 'min:0', 'max:300000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (! $this->has('rating') && ! $this->has('is_correct')) {
                $v->errors()->add('rating', 'Debes enviar rating (again|hard|good|easy) o is_correct (bool).');
            }
        });
    }

    /** Resuelve el rating final: prioriza el campo 'rating', convierte 'is_correct' si no hay. */
    public function resolvedRating(): string
    {
        if ($this->has('rating') && in_array($this->input('rating'), ['again','hard','good','easy'], true)) {
            return $this->input('rating');
        }
        return (bool) $this->input('is_correct', false) ? 'good' : 'again';
    }

    public function messages(): array
    {
        return [
            'rating.in'              => 'El rating debe ser: again, hard, good o easy.',
            'is_correct.boolean'     => 'El campo is_correct debe ser true o false.',
            'time_taken_ms.required' => 'El tiempo de respuesta es obligatorio.',
            'time_taken_ms.integer'  => 'El tiempo debe ser un número entero de milisegundos.',
            'time_taken_ms.min'      => 'El tiempo no puede ser negativo.',
            'time_taken_ms.max'      => 'El tiempo máximo permitido es 300.000 ms (5 minutos).',
        ];
    }
}
