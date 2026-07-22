<?php

namespace App\Livewire\Vocabulary;

use App\Models\Tag;
use App\Models\UserProgress;
use App\Models\UserSrsSettings;
use App\Services\SrsService;
use App\Support\TagCatalog;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Componente Livewire — Mi Colección Interactiva.
 *
 * Features:
 *   - Búsqueda en tiempo real por hangul / traducción
 *   - Filtro por card_state (new, learning, young, mature, relearning, suspended)
 *   - Filtro por tags (multicapa del catálogo estándar)
 *   - Filtro por vencimiento (due, today, week, future, all)
 *   - Ordenación múltiple
 *   - Toggle vista tabla / cards
 *   - Selección múltiple + acciones en lote
 *   - Edición inline de traducción
 *   - Ajuste manual de intervalo por tarjeta
 */
class MyCollection extends Component
{
    use WithPagination;

    // ── Filtros y búsqueda (persistidos en URL) ───────────────────────────────
    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'estado')]
    public string $filterState = '';

    #[Url(as: 'tag')]
    public string $filterTag = '';

    #[Url(as: 'vence')]
    public string $filterDue = '';   // 'due' | 'today' | 'week' | 'future' | ''

    #[Url(as: 'orden')]
    public string $sortBy = 'next_review_date';

    #[Url(as: 'dir')]
    public string $sortDir = 'asc';

    #[Url(as: 'vista')]
    public string $view = 'table'; // 'table' | 'cards'

    public int $perPage = 25;

    // ── Selección múltiple ────────────────────────────────────────────────────
    public array  $selected    = [];
    public bool   $selectAll   = false;

    // ── Estado de UI ──────────────────────────────────────────────────────────
    public ?int   $editingId        = null;   // ID del progress en edición inline
    public string $editingTranslation = '';

    public ?int   $intervalModalId   = null;  // ID del progress con modal de intervalo
    public int    $intervalDays      = 1;
    public bool   $intervalReset     = false;

    public string $flashMessage = '';
    public string $flashType    = 'success'; // 'success' | 'error'

    // ── Tags para los filtros ─────────────────────────────────────────────────
    public array $availableTags = [];

    // =========================================================================
    // Lifecycle
    // =========================================================================

    public function mount(): void
    {
        $this->availableTags = Tag::standard()
            ->orderBy('layer')
            ->orderBy('name')
            ->get(['id', 'name', 'layer'])
            ->groupBy('layer')
            ->toArray();
    }

    // =========================================================================
    // Query principal
    // =========================================================================

    #[Computed]
    public function items(): LengthAwarePaginator
    {
        $userId = auth()->id();

        $query = UserProgress::query()
            ->with(['item' => fn($q) => $q->with('tags'), 'item.entities'])
            ->where('user_progress.user_id', $userId)
            ->where('user_progress.item_type', 'compound')
            ->join('compounds', function ($join) {
                $join->on('user_progress.item_id', '=', 'compounds.id')
                     ->where('user_progress.item_type', 'compound');
            });

        // Búsqueda en texto
        if ($this->search !== '') {
            $q = '%' . $this->search . '%';
            $query->where(fn($x) => $x
                ->where('compounds.full_text', 'like', $q)
                ->orWhere('compounds.translation', 'like', $q)
            );
        }

        // Filtro por estado
        if ($this->filterState !== '') {
            $query->where('user_progress.card_state', $this->filterState);
        }

        // Filtro por tag
        if ($this->filterTag !== '') {
            $query->whereHas('item.tags', fn($q) => $q->where('tags.name', $this->filterTag));
        }

        // Filtro por vencimiento
        match ($this->filterDue) {
            'due'    => $query->where('user_progress.next_review_date', '<',  now()->toDateString())
                              ->where('user_progress.card_state', '!=', UserProgress::STATE_SUSPENDED),
            'today'  => $query->whereDate('user_progress.next_review_date', '=', today()),
            'week'   => $query->whereBetween('user_progress.next_review_date', [today(), today()->addDays(7)]),
            'future' => $query->where('user_progress.next_review_date', '>',  now()->addDays(7)->toDateString()),
            default  => null,
        };

        // Ordenación
        $col = match($this->sortBy) {
            'hangul'      => 'compounds.full_text',
            'translation' => 'compounds.translation',
            'state'       => 'user_progress.card_state',
            'interval'    => 'user_progress.interval_days',
            'lapses'      => 'user_progress.lapses',
            default       => 'user_progress.next_review_date',
        };

        $query->orderBy($col, $this->sortDir)
              ->select('user_progress.*', 'compounds.full_text', 'compounds.translation as compound_translation');

        return $query->paginate($this->perPage);
    }

    #[Computed]
    public function stats(): array
    {
        $userId = auth()->id();

        $counts = UserProgress::where('user_id', $userId)
            ->where('item_type', 'compound')
            ->select('card_state', DB::raw('count(*) as total'))
            ->groupBy('card_state')
            ->pluck('total', 'card_state')
            ->toArray();

        return [
            'total'      => array_sum($counts),
            'new'        => $counts[UserProgress::STATE_NEW]        ?? 0,
            'learning'   => $counts[UserProgress::STATE_LEARNING]   ?? 0,
            'young'      => $counts[UserProgress::STATE_YOUNG]      ?? 0,
            'mature'     => $counts[UserProgress::STATE_MATURE]      ?? 0,
            'relearning' => $counts[UserProgress::STATE_RELEARNING] ?? 0,
            'suspended'  => $counts[UserProgress::STATE_SUSPENDED]  ?? 0,
            'due'        => UserProgress::forUser($userId)->dueToday()->count(),
        ];
    }

    // =========================================================================
    // Acciones de búsqueda / filtros
    // =========================================================================

    public function updatedSearch(): void    { $this->resetPage(); $this->selected = []; }
    public function updatedFilterState(): void { $this->resetPage(); $this->selected = []; }
    public function updatedFilterTag(): void  { $this->resetPage(); $this->selected = []; }
    public function updatedFilterDue(): void  { $this->resetPage(); $this->selected = []; }
    public function updatedPerPage(): void    { $this->resetPage(); }

    public function sortOn(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $column;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search      = '';
        $this->filterState = '';
        $this->filterTag   = '';
        $this->filterDue   = '';
        $this->resetPage();
        $this->selected = [];
    }

    // =========================================================================
    // Selección múltiple
    // =========================================================================

    public function updatedSelectAll(bool $val): void
    {
        $this->selected = $val
            ? $this->items->pluck('id')->map(fn($id) => (string)$id)->toArray()
            : [];
    }

    public function toggleSelect(int $id): void
    {
        $key = (string)$id;
        if (in_array($key, $this->selected)) {
            $this->selected = array_values(array_filter($this->selected, fn($v) => $v !== $key));
        } else {
            $this->selected[] = $key;
        }
        $this->selectAll = false;
    }

    // =========================================================================
    // Edición inline de traducción
    // =========================================================================

    public function startEdit(int $progressId): void
    {
        $progress = UserProgress::where('id', $progressId)
            ->where('user_id', auth()->id())->firstOrFail();

        $this->editingId          = $progressId;
        $this->editingTranslation = $progress->item->translation ?? '';
    }

    public function saveTranslation(): void
    {
        if (! $this->editingId) return;

        $this->validate(['editingTranslation' => ['required', 'string', 'max:255']]);

        $progress = UserProgress::where('id', $this->editingId)
            ->where('user_id', auth()->id())->firstOrFail();

        $progress->item->update(['translation' => $this->editingTranslation]);

        $this->editingId = null;
        $this->flash('Traducción actualizada', 'success');
        unset($this->items); // invalida computed
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    // =========================================================================
    // Suspender / reactivar
    // =========================================================================

    public function toggleSuspend(int $progressId): void
    {
        $progress = UserProgress::where('id', $progressId)
            ->where('user_id', auth()->id())->firstOrFail();

        $newState = $progress->card_state === UserProgress::STATE_SUSPENDED
            ? UserProgress::STATE_NEW
            : UserProgress::STATE_SUSPENDED;

        $progress->update(['card_state' => $newState]);

        $label = $newState === UserProgress::STATE_SUSPENDED ? 'Tarjeta suspendida' : 'Tarjeta reactivada';
        $this->flash($label, 'success');
        unset($this->items);
    }

    // =========================================================================
    // Ajuste manual de intervalo
    // =========================================================================

    public function openIntervalModal(int $progressId): void
    {
        $progress = UserProgress::where('id', $progressId)
            ->where('user_id', auth()->id())->firstOrFail();

        $this->intervalModalId = $progressId;
        $this->intervalDays    = $progress->interval_days;
        $this->intervalReset   = false;
    }

    public function saveInterval(SrsService $srsService): void
    {
        if (! $this->intervalModalId) return;

        $this->validate([
            'intervalDays' => ['required', 'integer', 'min:0', 'max:36500'],
        ]);

        $progress = UserProgress::where('id', $this->intervalModalId)
            ->where('user_id', auth()->id())->firstOrFail();

        $settings = UserSrsSettings::firstOrCreate(['user_id' => auth()->id()]);

        if ($this->intervalReset) {
            $steps = $settings->getLearningStepsArray();
            $progress->update([
                'card_state'          => UserProgress::STATE_LEARNING,
                'interval_days'       => 0,
                'repetitions'         => 0,
                'lapses'              => 0,
                'learning_step_index' => 0,
                'ease_factor'         => 2.5,
                'next_review_date'    => now()->addMinutes($steps[0] ?? 1)->toDateTimeString(),
            ]);
        } else {
            $days      = (int) $this->intervalDays;
            $cardState = $days >= UserProgress::MATURE_THRESHOLD_DAYS
                ? UserProgress::STATE_MATURE
                : ($days > 0 ? UserProgress::STATE_YOUNG : UserProgress::STATE_LEARNING);

            $progress->update([
                'interval_days'    => $days,
                'card_state'       => $cardState,
                'next_review_date' => now()->addDays($days)->toDateString(),
            ]);
        }

        $this->intervalModalId = null;
        $this->flash('Intervalo actualizado', 'success');
        unset($this->items);
    }

    public function closeIntervalModal(): void
    {
        $this->intervalModalId = null;
    }

    // =========================================================================
    // Eliminar tarjeta
    // =========================================================================

    public function deleteCard(int $progressId): void
    {
        UserProgress::where('id', $progressId)
            ->where('user_id', auth()->id())
            ->delete();

        $this->flash('Tarjeta eliminada', 'success');
        $this->selected = array_filter($this->selected, fn($v) => $v !== (string)$progressId);
        unset($this->items);
    }

    // =========================================================================
    // Acciones en lote
    // =========================================================================

    public function batchAction(string $action): void
    {
        if (empty($this->selected)) {
            $this->flash('Selecciona al menos una tarjeta', 'error');
            return;
        }

        $ids    = array_map('intval', $this->selected);
        $userId = auth()->id();

        $owned = UserProgress::where('user_id', $userId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->toArray();

        DB::transaction(function () use ($owned, $action, $userId) {
            switch ($action) {
                case 'suspend':
                    UserProgress::whereIn('id', $owned)
                        ->update(['card_state' => UserProgress::STATE_SUSPENDED]);
                    break;
                case 'unsuspend':
                    UserProgress::whereIn('id', $owned)
                        ->update(['card_state' => UserProgress::STATE_NEW]);
                    break;
                case 'reset':
                    $settings = UserSrsSettings::firstOrCreate(['user_id' => $userId]);
                    $steps    = $settings->getLearningStepsArray();
                    UserProgress::whereIn('id', $owned)->update([
                        'card_state'          => UserProgress::STATE_LEARNING,
                        'interval_days'       => 0,
                        'repetitions'         => 0,
                        'lapses'              => 0,
                        'learning_step_index' => 0,
                        'ease_factor'         => 2.5,
                        'next_review_date'    => now()->addMinutes($steps[0] ?? 1)->toDateTimeString(),
                    ]);
                    break;
                case 'delete':
                    UserProgress::whereIn('id', $owned)->delete();
                    break;
            }
        });

        $count = count($owned);
        $labels = [
            'suspend'   => "suspendidas",
            'unsuspend' => "reactivadas",
            'reset'     => "reseteadas a Learning",
            'delete'    => "eliminadas",
        ];
        $this->flash("{$count} tarjetas {$labels[$action]}", 'success');
        $this->selected  = [];
        $this->selectAll = false;
        unset($this->items);
    }

    // =========================================================================
    // Helper flash
    // =========================================================================

    private function flash(string $message, string $type = 'success'): void
    {
        $this->flashMessage = $message;
        $this->flashType    = $type;

        $this->dispatch('flash-shown');
    }

    public function dismissFlash(): void
    {
        $this->flashMessage = '';
    }

    // =========================================================================
    // Render
    // =========================================================================

    public function render()
    {
        return view('livewire.vocabulary.my-collection');
    }
}
