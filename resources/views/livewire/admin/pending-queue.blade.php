<div>
    {{-- Mensaje de feedback --}}
    @if($message)
        <div style="
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.25rem;
            font-size: 0.875rem;
            background: {{ $msgType === 'success' ? 'rgba(52,211,153,0.12)' : 'rgba(248,113,113,0.12)' }};
            color: {{ $msgType === 'success' ? 'var(--color-success)' : 'var(--color-danger)' }};
            border: 1px solid {{ $msgType === 'success' ? 'rgba(52,211,153,0.25)' : 'rgba(248,113,113,0.25)' }};
            display: flex;
            align-items: center;
            justify-content: space-between;
        ">
            <span>{{ $message }}</span>
            <button wire:click="clearMessage" style="background:none;border:none;cursor:pointer;color:inherit;font-size:1rem;">✕</button>
        </div>
    @endif

    {{-- Cabecera --}}
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem;">
        <div>
            <h2 style="font-size:1.25rem; font-weight:700;">Cola de revisión</h2>
            <p style="color:var(--color-text-muted); font-size:0.85rem;">Términos con estado <em>pending_review</em></p>
        </div>
        <div style="background:rgba(251,191,36,0.15); border:1px solid rgba(251,191,36,0.3); border-radius:20px; padding:0.3rem 0.85rem; font-size:0.8rem; color:var(--color-warning);">
            {{ $queue->total() }} pendientes
        </div>
    </div>

    {{-- Lista de compounds pendientes --}}
    @if($queue->isEmpty())
        <div style="text-align:center; padding:3rem 1rem; color:var(--color-text-muted);">
            <div style="font-size:3rem; margin-bottom:1rem;">🎉</div>
            <p>¡Cola vacía! Todos los términos están revisados.</p>
        </div>
    @else
        <div style="display:grid; gap:0.75rem;">
            @foreach($queue as $compound)
                <div style="
                    background: var(--color-bg-card);
                    border: 1px solid {{ $selectedId === $compound->id ? 'rgba(124,110,245,0.5)' : 'var(--color-border)' }};
                    border-radius: 14px;
                    overflow: hidden;
                    transition: border-color 0.2s;
                ">
                    {{-- Cabecera del item --}}
                    <div style="
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 1rem 1.25rem;
                        cursor: pointer;
                    " wire:click="select({{ $compound->id }})">
                        <div>
                            <div style="display:flex; align-items:baseline; gap:0.75rem;">
                                <span style="font-size:1.5rem; font-weight:700;">{{ $compound->full_text }}</span>
                                <span style="color:var(--color-text-muted); font-size:0.9rem;">{{ $compound->translation }}</span>
                            </div>
                            {{-- Tags --}}
                            @if($compound->tags->isNotEmpty())
                                <div style="display:flex; flex-wrap:wrap; gap:0.3rem; margin-top:0.4rem;">
                                    @foreach($compound->tags as $tag)
                                        <span style="background:rgba(124,110,245,0.12); border:1px solid rgba(124,110,245,0.25); border-radius:20px; padding:0.1rem 0.5rem; font-size:0.7rem; color:var(--color-accent-soft);">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        {{-- Acciones rápidas --}}
                        <div style="display:flex; gap:0.5rem; flex-shrink:0; margin-left:1rem;" onclick="event.stopPropagation()">
                            <button
                                wire:click="approve({{ $compound->id }})"
                                wire:confirm="¿Aprobar este término sin edición?"
                                style="padding:0.4rem 0.9rem; background:rgba(52,211,153,0.15); color:var(--color-success); border:1px solid rgba(52,211,153,0.3); border-radius:8px; cursor:pointer; font-size:0.8rem; font-weight:600; font-family:inherit; transition:all 0.2s;"
                            >✓ Aprobar</button>
                            <button
                                wire:click="delete({{ $compound->id }})"
                                wire:confirm="⚠️ ¿Eliminar este término? Se borrará de todas las colecciones de usuarios. Esta acción no se puede deshacer."
                                style="padding:0.4rem 0.9rem; background:rgba(248,113,113,0.12); color:var(--color-danger); border:1px solid rgba(248,113,113,0.25); border-radius:8px; cursor:pointer; font-size:0.8rem; font-weight:600; font-family:inherit; transition:all 0.2s;"
                            >🗑 Eliminar</button>
                            <span style="color:var(--color-text-muted); display:flex; align-items:center; font-size:0.9rem;">{{ $selectedId === $compound->id ? '▲' : '▼' }}</span>
                        </div>
                    </div>

                    {{-- Panel de edición expandible --}}
                    @if($selectedId === $compound->id)
                        <div style="border-top:1px solid var(--color-border); padding:1.25rem;">
                            @livewire('admin.review-item', ['compoundId' => $compound->id], key('review-'.$compound->id))
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Paginación --}}
        @if($queue->hasPages())
            <div style="margin-top:1.25rem; display:flex; justify-content:center; gap:0.5rem;">
                @if($queue->onFirstPage())
                    <span style="padding:0.4rem 0.85rem; border-radius:8px; background:rgba(255,255,255,0.04); color:var(--color-text-muted); font-size:0.85rem;">← Anterior</span>
                @else
                    <button wire:click="previousPage" style="padding:0.4rem 0.85rem; border-radius:8px; background:var(--color-accent); color:#fff; border:none; cursor:pointer; font-size:0.85rem; font-family:inherit;">← Anterior</button>
                @endif
                <span style="padding:0.4rem 0.85rem; border-radius:8px; background:rgba(255,255,255,0.04); color:var(--color-text-muted); font-size:0.85rem;">{{ $queue->currentPage() }} / {{ $queue->lastPage() }}</span>
                @if($queue->hasMorePages())
                    <button wire:click="nextPage" style="padding:0.4rem 0.85rem; border-radius:8px; background:var(--color-accent); color:#fff; border:none; cursor:pointer; font-size:0.85rem; font-family:inherit;">Siguiente →</button>
                @else
                    <span style="padding:0.4rem 0.85rem; border-radius:8px; background:rgba(255,255,255,0.04); color:var(--color-text-muted); font-size:0.85rem;">Siguiente →</span>
                @endif
            </div>
        @endif
    @endif
</div>
