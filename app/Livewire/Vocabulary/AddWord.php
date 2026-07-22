<?php

namespace App\Livewire\Vocabulary;

use App\Services\VocabularyIngestService;
use Livewire\Component;

/**
 * Componente Livewire para añadir una palabra coreana a la colección del usuario.
 *
 * Flujo UX:
 *   1. Usuario escribe el texto en hangul
 *   2. Al enviar → VocabularyIngestService::ingest()
 *   3. Si Hit   → mensaje inmediato de éxito con los datos
 *   4. Si Miss  → mensaje "Analizando..." (el Job corre en background)
 */
class AddWord extends Component
{
    public string $text    = '';
    public string $status  = '';   // 'hit' | 'pending' | 'error'
    public string $message = '';
    public ?array $compound = null;

    protected function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'text.required' => 'Escribe una palabra o frase en coreano.',
            'text.max'      => 'El texto no puede superar los 255 caracteres.',
        ];
    }

    public function submit(VocabularyIngestService $service): void
    {
        $this->validate();

        try {
            $result = $service->ingest(
                text:   trim($this->text),
                userId: auth()->id(),
            );

            $this->status   = $result['status'];
            $this->message  = $result['message'];
            $this->compound = $result['compound']
                ? [
                    'full_text'   => $result['compound']->full_text,
                    'translation' => $result['compound']->translation,
                    'entities'    => $result['compound']->entities->map(fn ($e) => [
                        'text'    => $e->text,
                        'type'    => $e->type,
                        'meaning' => $e->meaning,
                    ])->toArray(),
                    'tags' => $result['compound']->tags->pluck('name')->toArray(),
                ]
                : null;

            $this->text = '';

        } catch (\Exception $e) {
            $this->status  = 'error';
            $this->message = 'Error al procesar la palabra. Por favor, inténtalo de nuevo.';
        }
    }

    public function resetForm(): void
    {
        $this->text     = '';
        $this->status   = '';
        $this->message  = '';
        $this->compound = null;
    }

    public function render()
    {
        return view('livewire.vocabulary.add-word');
    }
}
