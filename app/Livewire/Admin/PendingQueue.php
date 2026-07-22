<?php

namespace App\Livewire\Admin;

use App\Services\AdminCurationService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente Livewire de la cola de pendientes para el admin.
 *
 * Muestra los Compounds con status='pending_review' de forma paginada.
 * Permite aprobar o rechazar directamente desde la lista.
 * Al seleccionar un elemento, emite evento para abrir el ReviewItem.
 */
class PendingQueue extends Component
{
    use WithPagination;

    public ?int $selectedId = null;
    public string $message  = '';
    public string $msgType  = '';

    public function approve(int $id, AdminCurationService $service): void
    {
        try {
            $service->approve('compound', $id);
            $this->message = "✅ Compound #{$id} aprobado correctamente.";
            $this->msgType = 'success';
            $this->resetPage();
        } catch (\Throwable $e) {
            $this->message = "❌ Error al aprobar: " . $e->getMessage();
            $this->msgType = 'danger';
        }
    }

    public function delete(int $id, AdminCurationService $service): void
    {
        try {
            $service->delete('compound', $id);
            $this->message = "🗑️ Compound #{$id} eliminado con todos sus registros.";
            $this->msgType = 'success';
            $this->resetPage();
        } catch (\Throwable $e) {
            $this->message = "❌ Error al eliminar: " . $e->getMessage();
            $this->msgType = 'danger';
        }
    }

    public function select(int $id): void
    {
        $this->selectedId = ($this->selectedId === $id) ? null : $id;
    }

    public function clearMessage(): void
    {
        $this->message = '';
        $this->msgType = '';
    }

    public function render(AdminCurationService $service)
    {
        $queue = $service->getPendingQueue(10);

        return view('livewire.admin.pending-queue', ['queue' => $queue]);
    }
}
