<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'translation' => ['sometimes', 'string', 'max:500'],
            'meaning'     => ['sometimes', 'string', 'max:500'],
            'type'        => ['sometimes', 'string', 'in:root,particle,word'],
            'status'      => ['sometimes', 'string', 'in:pending_review,verified'],
            'tags'        => ['sometimes', 'array'],
            'tags.*'      => ['string', 'max:100'],
        ];
    }
}
