<div>
    {{-- ━━━━━━━━━━━━━━━━ Formulario de ingesta ━━━━━━━━━━━━━━━━ --}}
    <div style="background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 20px; padding: 2rem; box-shadow: 0 4px 24px rgba(0,0,0,0.3);">

        <h2 style="font-size: 1.3rem; font-weight: 700; margin-bottom: 0.3rem;">Añadir vocabulario</h2>
        <p style="color: var(--color-text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;">
            Escribe una palabra o frase en coreano para analizarla con IA
        </p>

        <form wire:submit="submit">
            <div style="display: flex; gap: 0.75rem; align-items: flex-start;">

                <div style="flex: 1;">
                    <input
                        id="word-input"
                        type="text"
                        wire:model="text"
                        placeholder="예: 학교에서, 안녕하세요..."
                        style="
                            width: 100%;
                            padding: 0.875rem 1.25rem;
                            background: rgba(255,255,255,0.04);
                            border: 1px solid {{ $errors->has('text') ? 'var(--color-danger)' : 'var(--color-border)' }};
                            border-radius: 12px;
                            color: var(--color-text);
                            font-size: 1.1rem;
                            font-family: inherit;
                            outline: none;
                            transition: border-color 0.2s, box-shadow 0.2s;
                        "
                        autocomplete="off"
                    >
                    @error('text')
                        <p style="color: var(--color-danger); font-size: 0.8rem; margin-top: 0.3rem;">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    style="
                        padding: 0.875rem 1.5rem;
                        background: var(--color-accent);
                        color: #fff;
                        border: none;
                        border-radius: 12px;
                        font-size: 1rem;
                        font-weight: 600;
                        cursor: pointer;
                        font-family: inherit;
                        transition: all 0.2s;
                        white-space: nowrap;
                    "
                >
                    <span wire:loading.remove>✨ Analizar</span>
                    <span wire:loading style="display: flex; align-items: center; gap: 0.4rem;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                        </svg>
                        Analizando...
                    </span>
                </button>
            </div>
        </form>
    </div>

    {{-- ━━━━━━━━━━━━━━━━ Resultado ━━━━━━━━━━━━━━━━ --}}
    @if($status)
        <div style="margin-top: 1.25rem;" wire:key="result-{{ $status }}">

            {{-- Error --}}
            @if($status === 'error')
                <div style="background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.3); border-radius: 12px; padding: 1rem 1.25rem; color: var(--color-danger);">
                    ❌ {{ $message }}
                    <button wire:click="resetForm" style="margin-left: 1rem; background: none; border: none; color: var(--color-danger); cursor: pointer; text-decoration: underline; font-size: 0.875rem;">Reintentar</button>
                </div>
            @endif

            {{-- Pending (Miss) — análisis en progreso --}}
            @if($status === 'pending')
                <div style="background: rgba(124,110,245,0.1); border: 1px solid rgba(124,110,245,0.3); border-radius: 12px; padding: 1.25rem 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                        <span style="font-size: 1.5rem;">⏳</span>
                        <strong style="color: var(--color-accent-soft);">Análisis en progreso</strong>
                    </div>
                    <p style="color: var(--color-text-muted); font-size: 0.875rem; margin: 0;">
                        {{ $message }} Puedes seguir añadiendo palabras mientras tanto.
                    </p>
                    <button wire:click="resetForm" style="margin-top: 0.75rem; background: none; border: 1px solid var(--color-border); border-radius: 8px; color: var(--color-text-muted); cursor: pointer; padding: 0.4rem 1rem; font-size: 0.8rem; font-family: inherit;">
                        Añadir otra palabra
                    </button>
                </div>
            @endif

            {{-- Hit — resultado inmediato --}}
            @if($status === 'hit' && $compound)
                <div style="background: rgba(52,211,153,0.08); border: 1px solid rgba(52,211,153,0.25); border-radius: 12px; padding: 1.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                        <span style="font-size: 1.5rem;">✅</span>
                        <strong style="color: var(--color-success);">¡Añadida a tu colección!</strong>
                    </div>

                    {{-- Palabra principal --}}
                    <div style="margin-bottom: 1rem;">
                        <span style="font-size: 2rem; font-weight: 700; color: var(--color-text);">{{ $compound['full_text'] }}</span>
                        <span style="margin-left: 1rem; color: var(--color-text-muted); font-size: 1rem;">{{ $compound['translation'] }}</span>
                    </div>

                    {{-- Morfemas --}}
                    @if(!empty($compound['entities']))
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                            @foreach($compound['entities'] as $entity)
                                <div style="
                                    background: rgba(255,255,255,0.05);
                                    border: 1px solid var(--color-border);
                                    border-radius: 8px;
                                    padding: 0.4rem 0.75rem;
                                    font-size: 0.85rem;
                                ">
                                    <span style="font-weight: 600; color: var(--color-accent-soft);">{{ $entity['text'] }}</span>
                                    <span style="color: var(--color-text-muted); font-size: 0.75rem; margin-left: 0.3rem;">[{{ $entity['type'] }}]</span>
                                    <br>
                                    <span style="color: var(--color-text-muted); font-size: 0.8rem;">{{ $entity['meaning'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Tags --}}
                    @if(!empty($compound['tags']))
                        <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                            @foreach($compound['tags'] as $tag)
                                <span style="background: rgba(124,110,245,0.15); border: 1px solid rgba(124,110,245,0.3); border-radius: 20px; padding: 0.2rem 0.7rem; font-size: 0.75rem; color: var(--color-accent-soft);">
                                    {{ $tag }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <button wire:click="resetForm" style="margin-top: 1rem; background: none; border: 1px solid var(--color-border); border-radius: 8px; color: var(--color-text-muted); cursor: pointer; padding: 0.4rem 1rem; font-size: 0.8rem; font-family: inherit;">
                        Añadir otra palabra
                    </button>
                </div>
            @endif
        </div>
    @endif
</div>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
