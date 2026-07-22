<?php

namespace App\Livewire\Admin;

use App\Models\Compound;
use App\Services\AdminCurationService;
use Livewire\Component;

/**
 * Componente Livewire para editar/aprobar/rechazar un término individual.
 * Se muestra como un panel expansible al seleccionar un compound de la cola.
 */
class ReviewItem extends Component
{
    public int    $compoundId;
    public string $translation = '';
    public array  $tags        = [];
    public string $tagsInput   = '';
    public string $message     = '';
    public string $msgType     = '';

    public function mount(int $compoundId): void
    {
        $this->compoundId = $compoundId;
        $compound         = Compound::with('tags')->findOrFail($compoundId);

        $this->translation = $compound->translation ?? '';
        $this->tags        = $compound->tags->pluck('name')->toArray();
        $this->tagsInput   = implode(', ', $this->tags);
    }

    public function save(AdminCurationService $service): void
    {
        $this->validate([
            'translation' => ['required', 'string', 'max:500'],
            'tagsInput'   => ['nullable', 'string'],
        ]);

        $tags = array_filter(array_map('trim', explode(',', $this->tagsInput)));

        try {
            $service->update('compound', $this->compoundId, [
                'translation' => $this->translation,
                'tags'        => $tags,
            ]);
            $this->message = '💾 Cambios guardados correctamente.';
            $this->msgType = 'success';
        } catch (\Throwable $e) {
            $this->message = '❌ Error al guardar: ' . $e->getMessage();
            $this->msgType = 'danger';
        }
    }

    public function approveAndSave(AdminCurationService $service): void
    {
        $this->save($service);

        if ($this->msgType !== 'danger') {
            try {
                $service->approve('compound', $this->compoundId);
                $this->message = '✅ Término guardado y aprobado correctamente.';
                $this->msgType = 'success';
                $this->dispatch('term-approved', id: $this->compoundId);
            } catch (\Throwable $e) {
                $this->message = '❌ Error al aprobar: ' . $e->getMessage();
                $this->msgType = 'danger';
            }
        }
    }

    public function render()
    {
        $compound = Compound::with(['entities', 'tags'])->find($this->compoundId);

        return view('livewire.admin.review-item', ['compound' => $compound]);
    }
}
