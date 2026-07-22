<div>
    @if($message)
        <div style="
            padding:0.65rem 1rem; border-radius:8px; margin-bottom:1rem; font-size:0.82rem;
            background:{{ $msgType === 'success' ? 'rgba(52,211,153,0.1)' : 'rgba(248,113,113,0.1)' }};
            color:{{ $msgType === 'success' ? 'var(--color-success)' : 'var(--color-danger)' }};
            border:1px solid {{ $msgType === 'success' ? 'rgba(52,211,153,0.25)' : 'rgba(248,113,113,0.25)' }};
        ">{{ $message }}</div>
    @endif

    @if($compound)
        {{-- Morfemas (solo lectura) --}}
        <div style="margin-bottom:1.25rem;">
            <p style="font-size:0.78rem; color:var(--color-text-muted); font-weight:600; letter-spacing:0.5px; text-transform:uppercase; margin-bottom:0.5rem;">Análisis morfológico (IA)</p>
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                @forelse($compound->entities as $entity)
                    <div style="background:rgba(255,255,255,0.04); border:1px solid var(--color-border); border-radius:8px; padding:0.4rem 0.75rem; font-size:0.82rem;">
                        <span style="font-weight:600; color:var(--color-accent-soft);">{{ $entity->text }}</span>
                        <span style="color:var(--color-text-muted); font-size:0.72rem;"> [{{ $entity->type }}]</span><br>
                        <span style="color:var(--color-text-muted);">{{ $entity->meaning }}</span>
                    </div>
                @empty
                    <span style="color:var(--color-text-muted); font-size:0.82rem;">Sin morfemas registrados</span>
                @endforelse
            </div>
        </div>

        {{-- Formulario de edición --}}
        <div style="display:grid; gap:1rem;">
            {{-- Traducción --}}
            <div>
                <label style="display:block; font-size:0.82rem; color:var(--color-text-muted); margin-bottom:0.35rem;">Traducción</label>
                <input
                    type="text"
                    wire:model="translation"
                    style="width:100%; padding:0.65rem 0.9rem; background:rgba(255,255,255,0.04); border:1px solid var(--color-border); border-radius:8px; color:var(--color-text); font-size:0.9rem; font-family:inherit; outline:none;"
                    placeholder="Traducción al español..."
                >
                @error('translation')
                    <p style="color:var(--color-danger); font-size:0.75rem; margin-top:0.25rem;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tags --}}
            <div>
                <label style="display:block; font-size:0.82rem; color:var(--color-text-muted); margin-bottom:0.35rem;">Etiquetas (separadas por comas)</label>
                <input
                    type="text"
                    wire:model="tagsInput"
                    style="width:100%; padding:0.65rem 0.9rem; background:rgba(255,255,255,0.04); border:1px solid var(--color-border); border-radius:8px; color:var(--color-text); font-size:0.9rem; font-family:inherit; outline:none;"
                    placeholder="Educación, Lugares, Formal..."
                >
            </div>

            {{-- Botones de acción --}}
            <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                <button
                    wire:click="approveAndSave"
                    wire:loading.attr="disabled"
                    style="padding:0.65rem 1.25rem; background:var(--color-accent); color:#fff; border:none; border-radius:8px; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:inherit; transition:all 0.2s;"
                >
                    <span wire:loading.remove wire:target="approveAndSave">✓ Guardar y Aprobar</span>
                    <span wire:loading wire:target="approveAndSave">Procesando...</span>
                </button>
                <button
                    wire:click="save"
                    wire:loading.attr="disabled"
                    style="padding:0.65rem 1.25rem; background:rgba(255,255,255,0.06); color:var(--color-text); border:1px solid var(--color-border); border-radius:8px; font-size:0.875rem; font-weight:500; cursor:pointer; font-family:inherit; transition:all 0.2s;"
                >
                    <span wire:loading.remove wire:target="save">💾 Solo guardar</span>
                    <span wire:loading wire:target="save">Guardando...</span>
                </button>
            </div>
        </div>
    @endif
</div>
