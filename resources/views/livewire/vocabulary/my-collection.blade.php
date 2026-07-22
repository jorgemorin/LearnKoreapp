<div x-data="{ confirmDelete: null }" x-on:flash-shown.window="setTimeout(() => $wire.dismissFlash(), 3000)">

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    {{-- Flash message                                                          --}}
    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    @if($flashMessage)
        <div wire:key="flash" style="
            position: fixed; top: 1.5rem; right: 1.5rem; z-index: 9999;
            padding: 0.75rem 1.25rem;
            background: {{ $flashType === 'success' ? 'rgba(52,211,153,0.15)' : 'rgba(248,113,113,0.15)' }};
            border: 1px solid {{ $flashType === 'success' ? 'rgba(52,211,153,0.4)' : 'rgba(248,113,113,0.4)' }};
            border-radius: 10px;
            color: {{ $flashType === 'success' ? 'var(--color-success)' : 'var(--color-danger)' }};
            font-size: 0.875rem; font-weight: 600;
            box-shadow: 0 8px 32px rgba(0,0,0,0.35);
            display: flex; align-items: center; gap: 0.5rem;
        ">
            {{ $flashType === 'success' ? '✓' : '✕' }} {{ $flashMessage }}
            <button wire:click="dismissFlash" style="margin-left: 0.5rem; background: none; border: none; cursor: pointer; color: inherit; font-size: 1rem; line-height: 1;">×</button>
        </div>
    @endif

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    {{-- Header + estadísticas                                                  --}}
    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem;">Palabras guardadas</h2>
            <p style="font-size: 0.8rem; color: var(--color-text-muted);">
                {{ $this->stats['total'] }} tarjetas · {{ $this->stats['due'] }} vencidas hoy
            </p>
        </div>
        {{-- Toggle vista --}}
        <div style="display: flex; gap: 0.35rem; background: rgba(255,255,255,0.04); border: 1px solid var(--color-border); border-radius: 8px; padding: 3px;">
            <button wire:click="$set('view', 'table')" id="btn-view-table" style="
                padding: 0.35rem 0.75rem; border-radius: 6px; border: none; cursor: pointer; font-size: 0.78rem; font-weight: 600;
                background: {{ $view === 'table' ? 'var(--color-accent)' : 'transparent' }};
                color: {{ $view === 'table' ? '#fff' : 'var(--color-text-muted)' }};
                font-family: inherit; transition: all 0.15s;
            ">≡ Tabla</button>
            <button wire:click="$set('view', 'cards')" id="btn-view-cards" style="
                padding: 0.35rem 0.75rem; border-radius: 6px; border: none; cursor: pointer; font-size: 0.78rem; font-weight: 600;
                background: {{ $view === 'cards' ? 'var(--color-accent)' : 'transparent' }};
                color: {{ $view === 'cards' ? '#fff' : 'var(--color-text-muted)' }};
                font-family: inherit; transition: all 0.15s;
            ">⊞ Cards</button>
        </div>
    </div>

    {{-- Stats chips por estado --}}
    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 1.5rem;">
        @php
            $stateData = [
                '' => ['label' => 'Todas', 'color' => 'var(--color-text-muted)', 'count' => $this->stats['total']],
                'new' => ['label' => 'Nuevas', 'color' => '#9ca3af', 'count' => $this->stats['new']],
                'learning' => ['label' => 'Aprendiendo', 'color' => '#fbbf24', 'count' => $this->stats['learning']],
                'young' => ['label' => 'Joven', 'color' => '#34d399', 'count' => $this->stats['young']],
                'mature' => ['label' => 'Madura', 'color' => '#818cf8', 'count' => $this->stats['mature']],
                'relearning' => ['label' => 'Reaprendiendo', 'color' => '#f87171', 'count' => $this->stats['relearning']],
                'suspended' => ['label' => 'Suspendidas', 'color' => '#6b7280', 'count' => $this->stats['suspended']],
            ];
        @endphp
        @foreach($stateData as $stateVal => $info)
            <button wire:click="$set('filterState', '{{ $stateVal }}')" style="
                padding: 0.3rem 0.75rem;
                border-radius: 20px;
                border: 1px solid {{ $filterState === $stateVal ? $info['color'] : 'var(--color-border)' }};
                background: {{ $filterState === $stateVal ? 'rgba(0,0,0,0.3)' : 'rgba(255,255,255,0.03)' }};
                color: {{ $filterState === $stateVal ? $info['color'] : 'var(--color-text-muted)' }};
                font-size: 0.75rem; font-weight: 600; cursor: pointer; font-family: inherit;
                transition: all 0.15s;
            ">{{ $info['label'] }} {{ $info['count'] }}</button>
        @endforeach
    </div>

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    {{-- Barra de búsqueda + filtros                                             --}}
    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    <div style="display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;">

        {{-- Buscador --}}
        <div style="flex: 1; min-width: 200px; position: relative;">
            <span style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--color-text-muted); font-size: 0.9rem; pointer-events: none;">🔍</span>
            <input
                wire:model.live.debounce.300ms="search"
                id="collection-search"
                type="text"
                placeholder="Buscar por hangul o traducción…"
                style="
                    width: 100%; padding: 0.6rem 0.75rem 0.6rem 2.25rem;
                    background: rgba(255,255,255,0.04);
                    border: 1px solid var(--color-border);
                    border-radius: 8px;
                    color: var(--color-text);
                    font-size: 0.875rem;
                    font-family: inherit;
                    outline: none;
                    transition: border-color 0.2s;
                "
                onfocus="this.style.borderColor='rgba(124,110,245,0.5)'"
                onblur="this.style.borderColor='var(--color-border)'"
            >
        </div>

        {{-- Filtro vencimiento --}}
        <select wire:model.live="filterDue" style="
            padding: 0.6rem 0.75rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--color-border);
            border-radius: 8px; color: var(--color-text);
            font-size: 0.8rem; font-family: inherit; cursor: pointer;
        ">
            <option value="">⏰ Todas las fechas</option>
            <option value="due">Vencidas</option>
            <option value="today">Hoy</option>
            <option value="week">Esta semana</option>
            <option value="future">Próximas</option>
        </select>

        {{-- Filtro tag --}}
        <select wire:model.live="filterTag" style="
            padding: 0.6rem 0.75rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--color-border);
            border-radius: 8px; color: var(--color-text);
            font-size: 0.8rem; font-family: inherit; cursor: pointer;
        ">
            <option value="">🏷 Todos los tags</option>
            @foreach($availableTags as $layer => $tags)
                <optgroup label="{{ ucfirst($layer) }}">
                    @foreach($tags as $tag)
                        <option value="{{ $tag['name'] }}">{{ $tag['name'] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>

        {{-- Por página --}}
        <select wire:model.live="perPage" style="
            padding: 0.6rem 0.5rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--color-border);
            border-radius: 8px; color: var(--color-text-muted);
            font-size: 0.8rem; font-family: inherit; cursor: pointer;
        ">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>

        {{-- Limpiar filtros --}}
        @if($search || $filterState || $filterTag || $filterDue)
            <button wire:click="clearFilters" style="
                padding: 0.6rem 0.75rem; border-radius: 8px;
                border: 1px solid rgba(248,113,113,0.3);
                background: rgba(248,113,113,0.08);
                color: var(--color-danger); font-size: 0.78rem;
                font-family: inherit; cursor: pointer;
            ">✕ Limpiar</button>
        @endif
    </div>

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    {{-- Barra de acciones en lote                                               --}}
    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    @if(!empty($selected))
        <div style="
            display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;
            padding: 0.7rem 1rem; margin-bottom: 1rem;
            background: rgba(124,110,245,0.08);
            border: 1px solid rgba(124,110,245,0.25);
            border-radius: 10px;
        ">
            <span style="font-size: 0.8rem; font-weight: 600; color: var(--color-accent-soft);">
                {{ count($selected) }} seleccionadas
            </span>
            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <button wire:click="batchAction('suspend')" style="padding: 0.35rem 0.75rem; border-radius: 6px; border: 1px solid rgba(251,191,36,0.3); background: rgba(251,191,36,0.08); color: #fbbf24; font-size: 0.78rem; font-family: inherit; cursor: pointer;">⏸ Suspender</button>
                <button wire:click="batchAction('unsuspend')" style="padding: 0.35rem 0.75rem; border-radius: 6px; border: 1px solid rgba(52,211,153,0.3); background: rgba(52,211,153,0.08); color: var(--color-success); font-size: 0.78rem; font-family: inherit; cursor: pointer;">▶ Reactivar</button>
                <button wire:click="batchAction('reset')" style="padding: 0.35rem 0.75rem; border-radius: 6px; border: 1px solid rgba(99,102,241,0.3); background: rgba(99,102,241,0.08); color: #818cf8; font-size: 0.78rem; font-family: inherit; cursor: pointer;">↺ Resetear</button>
                <button wire:click="batchAction('delete')"
                    x-on:click="if(!confirm('¿Eliminar ' + {{ count($selected) }} + ' tarjetas?')) $event.stopImmediatePropagation()"
                    style="padding: 0.35rem 0.75rem; border-radius: 6px; border: 1px solid rgba(248,113,113,0.3); background: rgba(248,113,113,0.08); color: var(--color-danger); font-size: 0.78rem; font-family: inherit; cursor: pointer;">🗑 Eliminar</button>
            </div>
        </div>
    @endif

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    {{-- Indicador de resultados                                                 --}}
    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    <div style="font-size: 0.78rem; color: var(--color-text-muted); margin-bottom: 0.75rem;">
        Mostrando {{ $this->items->firstItem() ?? 0 }}–{{ $this->items->lastItem() ?? 0 }} de {{ $this->items->total() }} tarjetas
        <span wire:loading wire:target="search,filterState,filterTag,filterDue,sortBy,perPage" style="margin-left: 0.5rem; opacity: 0.6;">cargando…</span>
    </div>

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    {{-- VISTA TABLA                                                             --}}
    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    @if($view === 'table')
        @if($this->items->isEmpty())
            <div style="text-align: center; padding: 3rem 1rem; background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 14px; color: var(--color-text-muted);">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🔍</div>
                <p>No hay tarjetas que coincidan con los filtros.</p>
                <button wire:click="clearFilters" style="margin-top: 0.75rem; padding: 0.5rem 1rem; border-radius: 8px; border: 1px solid var(--color-border); background: rgba(255,255,255,0.04); color: var(--color-text-muted); font-family: inherit; cursor: pointer; font-size: 0.8rem;">Limpiar filtros</button>
            </div>
        @else
        <div style="overflow-x: auto; border-radius: 12px; border: 1px solid var(--color-border);">
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="background: rgba(255,255,255,0.03); border-bottom: 1px solid var(--color-border);">
                        <th style="padding: 0.65rem 0.75rem; text-align: center; width: 2.5rem;">
                            <input type="checkbox" wire:model.live="selectAll" id="select-all-cb"
                                style="accent-color: var(--color-accent); cursor: pointer; width: 15px; height: 15px;">
                        </th>
                        <th style="padding: 0.65rem 0.75rem; text-align: left;">
                            <button wire:click="sortOn('hangul')" style="background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 0.8rem; font-weight: 600; font-family: inherit; display: flex; align-items: center; gap: 0.25rem;">
                                Hangul {{ $sortBy === 'hangul' ? ($sortDir === 'asc' ? '↑' : '↓') : '' }}
                            </button>
                        </th>
                        <th style="padding: 0.65rem 0.75rem; text-align: left;">
                            <button wire:click="sortOn('translation')" style="background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 0.8rem; font-weight: 600; font-family: inherit;">
                                Traducción {{ $sortBy === 'translation' ? ($sortDir === 'asc' ? '↑' : '↓') : '' }}
                            </button>
                        </th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center;">
                            <button wire:click="sortOn('state')" style="background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 0.8rem; font-weight: 600; font-family: inherit;">
                                Estado {{ $sortBy === 'state' ? ($sortDir === 'asc' ? '↑' : '↓') : '' }}
                            </button>
                        </th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center;">
                            <button wire:click="sortOn('interval')" style="background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 0.8rem; font-weight: 600; font-family: inherit;">
                                Intervalo {{ $sortBy === 'interval' ? ($sortDir === 'asc' ? '↑' : '↓') : '' }}
                            </button>
                        </th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center;">
                            <button wire:click="sortOn('next_review_date')" style="background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 0.8rem; font-weight: 600; font-family: inherit;">
                                Próximo repaso {{ $sortBy === 'next_review_date' ? ($sortDir === 'asc' ? '↑' : '↓') : '' }}
                            </button>
                        </th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center; color: var(--color-text-muted); font-size: 0.8rem; font-weight: 600;">
                            <button wire:click="sortOn('lapses')" style="background: none; border: none; cursor: pointer; color: var(--color-text-muted); font-size: 0.8rem; font-weight: 600; font-family: inherit;">
                                Fallos
                            </button>
                        </th>
                        <th style="padding: 0.65rem 0.75rem; text-align: center; color: var(--color-text-muted); font-size: 0.8rem; font-weight: 600;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->items as $item)
                        @php
                            $compound = $item->item;
                            $isEditing = $editingId === $item->id;
                            $stateColors = [
                                'new'        => ['text' => '#9ca3af', 'bg' => 'rgba(156,163,175,0.1)'],
                                'learning'   => ['text' => '#fbbf24', 'bg' => 'rgba(251,191,36,0.1)'],
                                'young'      => ['text' => '#34d399', 'bg' => 'rgba(52,211,153,0.1)'],
                                'mature'     => ['text' => '#818cf8', 'bg' => 'rgba(99,102,241,0.1)'],
                                'relearning' => ['text' => '#f87171', 'bg' => 'rgba(248,113,113,0.1)'],
                                'suspended'  => ['text' => '#6b7280', 'bg' => 'rgba(107,114,128,0.1)'],
                            ];
                            $sc = $stateColors[$item->card_state] ?? $stateColors['new'];
                            $dueDate = $item->next_review_date;
                            $isDue   = $dueDate && $dueDate->isPast() && !$dueDate->isToday();
                            $isToday = $dueDate && $dueDate->isToday();
                        @endphp
                        <tr wire:key="row-{{ $item->id }}" style="
                            border-bottom: 1px solid rgba(255,255,255,0.04);
                            background: {{ in_array((string)$item->id, $selected) ? 'rgba(124,110,245,0.06)' : 'transparent' }};
                            transition: background 0.15s;
                            opacity: {{ $item->card_state === 'suspended' ? '0.55' : '1' }};
                        " onmouseover="this.style.background='rgba(255,255,255,0.03)'" onmouseout="this.style.background='{{ in_array((string)$item->id, $selected) ? 'rgba(124,110,245,0.06)' : 'transparent' }}'">

                            {{-- Checkbox --}}
                            <td style="padding: 0.6rem 0.75rem; text-align: center;">
                                <input type="checkbox"
                                    wire:click="toggleSelect({{ $item->id }})"
                                    @checked(in_array((string)$item->id, $selected))
                                    style="accent-color: var(--color-accent); cursor: pointer; width: 15px; height: 15px;">
                            </td>

                            {{-- Hangul --}}
                            <td style="padding: 0.6rem 0.75rem;">
                                <span style="font-size: 1.1rem; font-weight: 700; color: var(--color-text);">
                                    {{ $compound?->full_text ?? '—' }}
                                </span>
                            </td>

                            {{-- Traducción (edición inline) --}}
                            <td style="padding: 0.6rem 0.75rem;">
                                @if($isEditing)
                                    <div style="display: flex; gap: 0.35rem; align-items: center;">
                                        <input wire:model="editingTranslation" type="text"
                                            style="flex: 1; padding: 0.3rem 0.5rem; background: rgba(255,255,255,0.06); border: 1px solid rgba(124,110,245,0.5); border-radius: 6px; color: var(--color-text); font-size: 0.85rem; font-family: inherit; outline: none; min-width: 100px;"
                                            wire:keydown.enter="saveTranslation"
                                            wire:keydown.escape="cancelEdit">
                                        <button wire:click="saveTranslation" style="padding: 0.25rem 0.5rem; border-radius: 5px; border: none; background: var(--color-accent); color: #fff; cursor: pointer; font-size: 0.75rem;">✓</button>
                                        <button wire:click="cancelEdit"      style="padding: 0.25rem 0.5rem; border-radius: 5px; border: 1px solid var(--color-border); background: transparent; color: var(--color-text-muted); cursor: pointer; font-size: 0.75rem;">✕</button>
                                    </div>
                                @else
                                    <span style="color: var(--color-text-muted);">{{ $compound?->translation ?? '—' }}</span>
                                @endif
                            </td>

                            {{-- Estado --}}
                            <td style="padding: 0.6rem 0.75rem; text-align: center;">
                                <span style="
                                    display: inline-block;
                                    padding: 0.2rem 0.6rem; border-radius: 20px;
                                    font-size: 0.7rem; font-weight: 600;
                                    background: {{ $sc['bg'] }}; color: {{ $sc['text'] }};
                                ">{{ $item->stateLabel() }}</span>
                            </td>

                            {{-- Intervalo --}}
                            <td style="padding: 0.6rem 0.75rem; text-align: center; color: var(--color-text-muted); font-size: 0.8rem;">
                                {{ $item->interval_days > 0 ? $item->interval_days . 'd' : '<1d' }}
                            </td>

                            {{-- Próximo repaso --}}
                            <td style="padding: 0.6rem 0.75rem; text-align: center; font-size: 0.78rem;
                                color: {{ $isDue ? 'var(--color-danger)' : ($isToday ? 'var(--color-success)' : 'var(--color-text-muted)') }};">
                                @if($dueDate)
                                    @if($isDue) ⚠ Vencida
                                    @elseif($isToday) 🎯 Hoy
                                    @else {{ $dueDate->diffForHumans() }}
                                    @endif
                                @else —
                                @endif
                            </td>

                            {{-- Fallos --}}
                            <td style="padding: 0.6rem 0.75rem; text-align: center; font-size: 0.8rem; color: {{ $item->lapses > 0 ? 'var(--color-danger)' : 'var(--color-text-muted)' }};">
                                {{ $item->lapses }}
                            </td>

                            {{-- Acciones --}}
                            <td style="padding: 0.6rem 0.75rem; text-align: center;">
                                <div style="display: flex; gap: 0.3rem; justify-content: center;">
                                    {{-- Editar traducción --}}
                                    @unless($isEditing)
                                    <button wire:click="startEdit({{ $item->id }})" title="Editar traducción" style="padding: 0.28rem 0.5rem; border-radius: 5px; border: 1px solid var(--color-border); background: transparent; color: var(--color-text-muted); cursor: pointer; font-size: 0.78rem; transition: all 0.15s;"
                                        onmouseover="this.style.borderColor='rgba(124,110,245,0.5)'; this.style.color='var(--color-accent-soft)'"
                                        onmouseout="this.style.borderColor='var(--color-border)'; this.style.color='var(--color-text-muted)'">✏</button>
                                    @endunless
                                    {{-- Ajustar intervalo --}}
                                    <button wire:click="openIntervalModal({{ $item->id }})" title="Ajustar intervalo" style="padding: 0.28rem 0.5rem; border-radius: 5px; border: 1px solid var(--color-border); background: transparent; color: var(--color-text-muted); cursor: pointer; font-size: 0.78rem; transition: all 0.15s;"
                                        onmouseover="this.style.borderColor='rgba(99,102,241,0.5)'; this.style.color='#818cf8'"
                                        onmouseout="this.style.borderColor='var(--color-border)'; this.style.color='var(--color-text-muted)'">⏱</button>
                                    {{-- Suspender --}}
                                    <button wire:click="toggleSuspend({{ $item->id }})" title="{{ $item->card_state === 'suspended' ? 'Reactivar' : 'Suspender' }}" style="padding: 0.28rem 0.5rem; border-radius: 5px; border: 1px solid var(--color-border); background: transparent; color: var(--color-text-muted); cursor: pointer; font-size: 0.78rem; transition: all 0.15s;"
                                        onmouseover="this.style.borderColor='rgba(251,191,36,0.5)'; this.style.color='#fbbf24'"
                                        onmouseout="this.style.borderColor='var(--color-border)'; this.style.color='var(--color-text-muted)'">
                                        {{ $item->card_state === 'suspended' ? '▶' : '⏸' }}
                                    </button>
                                    {{-- Eliminar --}}
                                    <button
                                        x-on:click="if(confirm('¿Eliminar esta tarjeta de tu colección?')) $wire.deleteCard({{ $item->id }})"
                                        title="Eliminar" style="padding: 0.28rem 0.5rem; border-radius: 5px; border: 1px solid var(--color-border); background: transparent; color: var(--color-text-muted); cursor: pointer; font-size: 0.78rem; transition: all 0.15s;"
                                        onmouseover="this.style.borderColor='rgba(248,113,113,0.5)'; this.style.color='var(--color-danger)'"
                                        onmouseout="this.style.borderColor='var(--color-border)'; this.style.color='var(--color-text-muted)'">🗑</button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    {{-- VISTA CARDS                                                             --}}
    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    @else
        @if($this->items->isEmpty())
            <div style="text-align: center; padding: 3rem 1rem; background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 14px; color: var(--color-text-muted);">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🔍</div>
                <p>No hay tarjetas que coincidan con los filtros.</p>
            </div>
        @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 0.75rem;">
            @foreach($this->items as $item)
                @php
                    $compound = $item->item;
                    $stateColors = [
                        'new' => '#9ca3af', 'learning' => '#fbbf24', 'young' => '#34d399',
                        'mature' => '#818cf8', 'relearning' => '#f87171', 'suspended' => '#6b7280',
                    ];
                    $color = $stateColors[$item->card_state] ?? '#9ca3af';
                    $dueDate = $item->next_review_date;
                    $isDue   = $dueDate && $dueDate->isPast() && !$dueDate->isToday();
                    $isToday = $dueDate && $dueDate->isToday();
                @endphp
                <div wire:key="card-{{ $item->id }}" style="
                    background: var(--color-bg-card);
                    border: 1px solid var(--color-border);
                    border-radius: 14px;
                    padding: 1.1rem 1.25rem;
                    transition: border-color 0.2s;
                    opacity: {{ $item->card_state === 'suspended' ? '0.55' : '1' }};
                    position: relative;
                " onmouseover="this.style.borderColor='rgba(124,110,245,0.35)'" onmouseout="this.style.borderColor='var(--color-border)'">

                    {{-- Estado chip --}}
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
                        <span style="font-size: 0.68rem; font-weight: 600; color: {{ $color }}; background: rgba(0,0,0,0.2); padding: 0.15rem 0.5rem; border-radius: 20px;">
                            {{ $item->stateLabel() }}
                        </span>
                        <span style="font-size: 0.7rem; color: {{ $isDue ? 'var(--color-danger)' : ($isToday ? 'var(--color-success)' : 'var(--color-text-muted)') }};">
                            @if($dueDate)
                                @if($isDue) ⚠ Vencida @elseif($isToday) 🎯 Hoy @else {{ $dueDate->diffForHumans() }} @endif
                            @endif
                        </span>
                    </div>

                    {{-- Hangul --}}
                    <div style="font-size: 1.6rem; font-weight: 700; color: var(--color-text); margin-bottom: 0.25rem; line-height: 1.2;">
                        {{ $compound?->full_text ?? '—' }}
                    </div>

                    {{-- Traducción --}}
                    <div style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 0.7rem;">
                        {{ $compound?->translation ?? '—' }}
                    </div>

                    {{-- Tags --}}
                    @if($compound?->tags?->isNotEmpty())
                        <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-bottom: 0.7rem;">
                            @foreach($compound->tags->take(3) as $tag)
                                <span style="background: rgba(124,110,245,0.1); border: 1px solid rgba(124,110,245,0.2); border-radius: 20px; padding: 0.1rem 0.45rem; font-size: 0.68rem; color: var(--color-accent-soft);">{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Info SRS --}}
                    <div style="display: flex; gap: 0.75rem; font-size: 0.72rem; color: var(--color-text-muted); margin-bottom: 0.7rem;">
                        <span>{{ $item->interval_days }}d</span>
                        <span>Reps: {{ $item->repetitions }}</span>
                        @if($item->lapses > 0)<span style="color: var(--color-danger);">↩ {{ $item->lapses }}</span>@endif
                    </div>

                    {{-- Acciones rápidas --}}
                    <div style="display: flex; gap: 0.35rem;">
                        <button wire:click="startEdit({{ $item->id }})" title="Editar" style="flex: 1; padding: 0.3rem; border-radius: 6px; border: 1px solid var(--color-border); background: transparent; color: var(--color-text-muted); cursor: pointer; font-size: 0.75rem; font-family: inherit; transition: all 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">✏</button>
                        <button wire:click="openIntervalModal({{ $item->id }})" title="Intervalo" style="flex: 1; padding: 0.3rem; border-radius: 6px; border: 1px solid var(--color-border); background: transparent; color: var(--color-text-muted); cursor: pointer; font-size: 0.75rem; font-family: inherit; transition: all 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">⏱</button>
                        <button wire:click="toggleSuspend({{ $item->id }})" title="Suspender" style="flex: 1; padding: 0.3rem; border-radius: 6px; border: 1px solid var(--color-border); background: transparent; color: var(--color-text-muted); cursor: pointer; font-size: 0.75rem; font-family: inherit; transition: all 0.15s;" onmouseover="this.style.background='rgba(255,255,255,0.06)'" onmouseout="this.style.background='transparent'">
                            {{ $item->card_state === 'suspended' ? '▶' : '⏸' }}
                        </button>
                        <button x-on:click="if(confirm('¿Eliminar?')) $wire.deleteCard({{ $item->id }})" title="Eliminar" style="flex: 1; padding: 0.3rem; border-radius: 6px; border: 1px solid var(--color-border); background: transparent; color: var(--color-text-muted); cursor: pointer; font-size: 0.75rem; font-family: inherit; transition: all 0.15s;" onmouseover="this.style.background='rgba(248,113,113,0.1)'; this.style.color='var(--color-danger)'" onmouseout="this.style.background='transparent'; this.style.color='var(--color-text-muted)'">🗑</button>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    @endif

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    {{-- Paginación                                                              --}}
    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    @if($this->items->hasPages())
        <div style="margin-top: 1.5rem; display: flex; justify-content: center; gap: 0.4rem; align-items: center; flex-wrap: wrap;">
            @if($this->items->onFirstPage())
                <span style="padding: 0.4rem 0.75rem; border-radius: 7px; background: rgba(255,255,255,0.03); color: var(--color-text-muted); font-size: 0.8rem; border: 1px solid var(--color-border);">← Anterior</span>
            @else
                <button wire:click="previousPage" style="padding: 0.4rem 0.75rem; border-radius: 7px; background: rgba(255,255,255,0.06); border: 1px solid var(--color-border); color: var(--color-text); font-family: inherit; cursor: pointer; font-size: 0.8rem; transition: all 0.15s;">← Anterior</button>
            @endif

            <span style="padding: 0.4rem 0.75rem; border-radius: 7px; background: rgba(124,110,245,0.12); border: 1px solid rgba(124,110,245,0.25); color: var(--color-accent-soft); font-size: 0.8rem; font-weight: 600;">
                {{ $this->items->currentPage() }} / {{ $this->items->lastPage() }}
            </span>

            @if($this->items->hasMorePages())
                <button wire:click="nextPage" style="padding: 0.4rem 0.75rem; border-radius: 7px; background: rgba(255,255,255,0.06); border: 1px solid var(--color-border); color: var(--color-text); font-family: inherit; cursor: pointer; font-size: 0.8rem; transition: all 0.15s;">Siguiente →</button>
            @else
                <span style="padding: 0.4rem 0.75rem; border-radius: 7px; background: rgba(255,255,255,0.03); color: var(--color-text-muted); font-size: 0.8rem; border: 1px solid var(--color-border);">Siguiente →</span>
            @endif
        </div>
    @endif

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    {{-- Modal: ajuste de intervalo                                              --}}
    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    @if($intervalModalId)
        <div style="
            position: fixed; inset: 0; z-index: 1000;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center; padding: 1rem;
        " wire:click.self="closeIntervalModal">
            <div style="
                background: var(--color-bg-card);
                border: 1px solid var(--color-border);
                border-radius: 18px;
                padding: 2rem;
                width: 100%; max-width: 380px;
                box-shadow: 0 24px 80px rgba(0,0,0,0.6);
            ">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1.25rem;">⏱ Ajustar intervalo</h3>

                <label style="display: block; font-size: 0.8rem; color: var(--color-text-muted); margin-bottom: 0.35rem;">Días hasta el próximo repaso</label>
                <input wire:model="intervalDays" type="number" min="0" max="36500"
                    style="width: 100%; padding: 0.65rem 0.75rem; background: rgba(255,255,255,0.06); border: 1px solid var(--color-border); border-radius: 8px; color: var(--color-text); font-size: 1rem; font-family: inherit; outline: none; margin-bottom: 1rem;"
                    onfocus="this.style.borderColor='rgba(124,110,245,0.5)'" onblur="this.style.borderColor='var(--color-border)'">

                <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1.25rem; cursor: pointer; font-size: 0.85rem;">
                    <input type="checkbox" wire:model="intervalReset" style="accent-color: var(--color-accent); width: 16px; height: 16px;">
                    Resetear a fase Learning (recomienzo desde cero)
                </label>

                <div style="display: flex; gap: 0.75rem;">
                    <button wire:click="saveInterval" style="flex: 1; padding: 0.65rem; background: var(--color-accent); color: #fff; border: none; border-radius: 10px; font-family: inherit; font-weight: 600; cursor: pointer; font-size: 0.9rem;">Guardar</button>
                    <button wire:click="closeIntervalModal" style="padding: 0.65rem 1rem; background: rgba(255,255,255,0.06); border: 1px solid var(--color-border); border-radius: 10px; color: var(--color-text-muted); font-family: inherit; cursor: pointer; font-size: 0.9rem;">Cancelar</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: edición inline en tarjeta cards view --}}
    @if($editingId && $view === 'cards')
        <div style="position: fixed; inset: 0; z-index: 1000; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 1rem;" wire:click.self="cancelEdit">
            <div style="background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 18px; padding: 2rem; width: 100%; max-width: 380px; box-shadow: 0 24px 80px rgba(0,0,0,0.6);">
                <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 1rem;">✏ Editar traducción</h3>
                <input wire:model="editingTranslation" type="text"
                    style="width: 100%; padding: 0.65rem 0.75rem; background: rgba(255,255,255,0.06); border: 1px solid rgba(124,110,245,0.4); border-radius: 8px; color: var(--color-text); font-size: 1rem; font-family: inherit; outline: none; margin-bottom: 1.25rem;"
                    wire:keydown.enter="saveTranslation" wire:keydown.escape="cancelEdit">
                <div style="display: flex; gap: 0.75rem;">
                    <button wire:click="saveTranslation" style="flex: 1; padding: 0.65rem; background: var(--color-accent); color: #fff; border: none; border-radius: 10px; font-family: inherit; font-weight: 600; cursor: pointer; font-size: 0.9rem;">Guardar</button>
                    <button wire:click="cancelEdit" style="padding: 0.65rem 1rem; background: rgba(255,255,255,0.06); border: 1px solid var(--color-border); border-radius: 10px; color: var(--color-text-muted); font-family: inherit; cursor: pointer; font-size: 0.9rem;">Cancelar</button>
                </div>
            </div>
        </div>
    @endif

</div>
