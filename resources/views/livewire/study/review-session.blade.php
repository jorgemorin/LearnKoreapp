<div>
    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    {{-- Sesión completada                                                      --}}
    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    @if($sessionComplete)
        <div style="
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            text-align: center;
            box-shadow: 0 8px 40px rgba(0,0,0,0.4);
            max-width: 500px;
            margin: 0 auto;
        ">
            @if(empty($cards))
                {{-- Sin tarjetas hoy --}}
                <div style="font-size: 4rem; margin-bottom: 1rem;">🎉</div>
                <h2 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--color-success);">
                    ¡Todo al día!
                </h2>
                <p style="color: var(--color-text-muted); margin-bottom: 2rem;">
                    No tienes tarjetas pendientes de repaso hoy.<br>
                    ¡Vuelve mañana o añade más vocabulario!
                </p>
                <a href="{{ route('collection') }}" style="
                    display: inline-block;
                    padding: 0.875rem 2rem;
                    background: var(--color-accent);
                    color: #fff;
                    border-radius: 12px;
                    text-decoration: none;
                    font-weight: 600;
                ">Ver mi colección</a>
            @else
                {{-- Resumen de sesión --}}
                @php
                    $total    = $correctCount + $incorrectCount;
                    $accuracy = $total > 0 ? round(($correctCount / $total) * 100) : 0;
                @endphp
                <div style="font-size: 4rem; margin-bottom: 1rem;">
                    @if($accuracy >= 80) 🏆
                    @elseif($accuracy >= 60) ✅
                    @else 💪
                    @endif
                </div>
                <h2 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 0.5rem;">
                    Sesión completada
                </h2>
                <p style="color: var(--color-text-muted); margin-bottom: 2rem;">Has repasado {{ $total }} tarjetas</p>

                {{-- Estadísticas Anki --}}
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; margin-bottom: 2rem;">
                    <div style="text-align: center; padding: 0.75rem 0.5rem; background: rgba(248,113,113,0.08); border: 1px solid rgba(248,113,113,0.2); border-radius: 10px;">
                        <div style="font-size: 1.8rem; font-weight: 700; color: #f87171;">{{ $againCount }}</div>
                        <div style="font-size: 0.72rem; color: var(--color-text-muted); margin-top: 2px;">Otra vez</div>
                    </div>
                    <div style="text-align: center; padding: 0.75rem 0.5rem; background: rgba(251,191,36,0.08); border: 1px solid rgba(251,191,36,0.2); border-radius: 10px;">
                        <div style="font-size: 1.8rem; font-weight: 700; color: #fbbf24;">{{ $hardCount }}</div>
                        <div style="font-size: 0.72rem; color: var(--color-text-muted); margin-top: 2px;">Difícil</div>
                    </div>
                    <div style="text-align: center; padding: 0.75rem 0.5rem; background: rgba(52,211,153,0.08); border: 1px solid rgba(52,211,153,0.2); border-radius: 10px;">
                        <div style="font-size: 1.8rem; font-weight: 700; color: var(--color-success);">{{ $goodCount }}</div>
                        <div style="font-size: 0.72rem; color: var(--color-text-muted); margin-top: 2px;">Bien</div>
                    </div>
                    <div style="text-align: center; padding: 0.75rem 0.5rem; background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.2); border-radius: 10px;">
                        <div style="font-size: 1.8rem; font-weight: 700; color: var(--color-accent-soft);">{{ $easyCount }}</div>
                        <div style="font-size: 0.72rem; color: var(--color-text-muted); margin-top: 2px;">Fácil</div>
                    </div>
                </div>

                {{-- Precisión global --}}
                <div style="display: flex; justify-content: center; gap: 2.5rem; margin-bottom: 2rem;">
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--color-success);">{{ $correctCount }}</div>
                        <div style="font-size: 0.8rem; color: var(--color-text-muted);">Correctas</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--color-danger);">{{ $incorrectCount }}</div>
                        <div style="font-size: 0.8rem; color: var(--color-text-muted);">Errores</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: 700; color: var(--color-accent-soft);">{{ $accuracy }}%</div>
                        <div style="font-size: 0.8rem; color: var(--color-text-muted);">Precisión</div>
                    </div>
                </div>

                <div style="background: rgba(255,255,255,0.06); border-radius: 100px; height: 8px; margin-bottom: 2rem; overflow: hidden;">
                    <div style="
                        height: 100%;
                        width: {{ $accuracy }}%;
                        background: {{ $accuracy >= 80 ? 'var(--color-success)' : ($accuracy >= 60 ? 'var(--color-accent)' : 'var(--color-danger)') }};
                        border-radius: 100px;
                        transition: width 1s ease;
                    "></div>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <button
                        wire:click="restart"
                        wire:loading.attr="disabled"
                        style="
                            padding: 0.875rem 1.75rem;
                            background: var(--color-accent);
                            color: #fff;
                            border: none;
                            border-radius: 12px;
                            font-size: 0.95rem;
                            font-weight: 600;
                            cursor: pointer;
                            font-family: inherit;
                            transition: all 0.2s;
                        "
                    >🔄 Nueva sesión</button>
                    <a href="{{ route('collection') }}" style="
                        padding: 0.875rem 1.75rem;
                        background: rgba(255,255,255,0.06);
                        color: var(--color-text-muted);
                        border: 1px solid var(--color-border);
                        border-radius: 12px;
                        text-decoration: none;
                        font-size: 0.95rem;
                        font-weight: 500;
                        transition: all 0.2s;
                    ">Mi colección</a>
                </div>
            @endif
        </div>

    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    {{-- Tarjeta activa                                                         --}}
    {{-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ --}}
    @else
        @php $card = $cards[$currentIndex] ?? null; @endphp
        @if($card)

            {{-- Progreso de sesión --}}
            <div style="max-width: 640px; margin: 0 auto 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                    <span style="font-size: 0.85rem; color: var(--color-text-muted);">
                        Tarjeta {{ $currentIndex + 1 }} de {{ count($cards) }}
                    </span>
                    <div style="display: flex; gap: 0.75rem; font-size: 0.8rem;">
                        <span style="color: #f87171;" title="Otra vez">↩ {{ $againCount }}</span>
                        <span style="color: #fbbf24;" title="Difícil">◐ {{ $hardCount }}</span>
                        <span style="color: var(--color-success);" title="Bien">✓ {{ $goodCount }}</span>
                        <span style="color: var(--color-accent-soft);" title="Fácil">★ {{ $easyCount }}</span>
                    </div>
                </div>
                <div style="background: rgba(255,255,255,0.06); border-radius: 100px; height: 4px; overflow: hidden;">
                    <div style="
                        height: 100%;
                        width: {{ count($cards) > 0 ? round(($currentIndex / count($cards)) * 100) : 0 }}%;
                        background: var(--color-accent);
                        border-radius: 100px;
                        transition: width 0.4s ease;
                    "></div>
                </div>
            </div>

            {{-- Tarjeta de flashcard --}}
            <div style="
                max-width: 640px;
                margin: 0 auto;
                background: var(--color-bg-card);
                border: 1px solid {{ $showAnswer ? 'rgba(124,110,245,0.4)' : 'var(--color-border)' }};
                border-radius: 24px;
                padding: 2.5rem 2.5rem;
                text-align: center;
                box-shadow: 0 8px 40px rgba(0,0,0,0.4);
                transition: border-color 0.3s;
                min-height: 280px;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            " wire:key="card-{{ $card['progress_id'] }}-{{ $showAnswer ? 'revealed' : 'hidden' }}">

                {{-- Chip de estado de madurez --}}
                <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 1rem;">
                    @php
                        $stateColors = [
                            'new'        => ['bg' => 'rgba(156,163,175,0.12)', 'border' => 'rgba(156,163,175,0.3)', 'text' => '#9ca3af'],
                            'learning'   => ['bg' => 'rgba(251,191,36,0.12)',  'border' => 'rgba(251,191,36,0.3)',  'text' => '#fbbf24'],
                            'young'      => ['bg' => 'rgba(52,211,153,0.12)',  'border' => 'rgba(52,211,153,0.3)',  'text' => '#34d399'],
                            'mature'     => ['bg' => 'rgba(99,102,241,0.12)',  'border' => 'rgba(99,102,241,0.3)',  'text' => '#818cf8'],
                            'relearning' => ['bg' => 'rgba(248,113,113,0.12)','border' => 'rgba(248,113,113,0.3)','text' => '#f87171'],
                        ];
                        $sc = $stateColors[$card['card_state']] ?? $stateColors['new'];
                    @endphp
                    <span style="
                        background: {{ $sc['bg'] }};
                        border: 1px solid {{ $sc['border'] }};
                        color: {{ $sc['text'] }};
                        border-radius: 20px;
                        padding: 0.2rem 0.65rem;
                        font-size: 0.7rem;
                        font-weight: 600;
                        letter-spacing: 0.5px;
                    ">{{ $card['state_label'] }}</span>
                    <span style="font-size: 0.72rem; font-weight: 600; letter-spacing: 1px; text-transform: uppercase; color: var(--color-accent-soft);">
                        {{ $card['type'] === 'compound' ? '📝 Compuesto' : '🔤 Morfema' }}
                    </span>
                </div>

                {{-- Hangul principal --}}
                <div style="font-size: 3.5rem; font-weight: 700; color: var(--color-text); margin-bottom: 0.5rem; line-height: 1.2;">
                    {{ $card['hangul'] }}
                </div>

                {{-- Tags --}}
                @if(!empty($card['tags']))
                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; justify-content: center; margin-bottom: 1.5rem;">
                        @foreach($card['tags'] as $tag)
                            <span style="
                                background: rgba(124,110,245,0.12);
                                border: 1px solid rgba(124,110,245,0.25);
                                border-radius: 20px;
                                padding: 0.15rem 0.6rem;
                                font-size: 0.72rem;
                                color: var(--color-accent-soft);
                            ">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif

                {{-- Respuesta oculta --}}
                @if(! $showAnswer)
                    <p style="color: var(--color-text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;">
                        ¿Sabes el significado?
                    </p>
                    <button
                        wire:click="reveal"
                        style="
                            padding: 0.875rem 2.5rem;
                            background: var(--color-accent);
                            color: #fff;
                            border: none;
                            border-radius: 12px;
                            font-size: 1rem;
                            font-weight: 600;
                            cursor: pointer;
                            font-family: inherit;
                            transition: all 0.2s;
                        "
                        onmouseover="this.style.background='var(--color-accent-soft)'; this.style.transform='translateY(-1px)';"
                        onmouseout="this.style.background='var(--color-accent)'; this.style.transform='none';"
                    >
                        Ver respuesta 👁
                    </button>

                {{-- Respuesta revelada --}}
                @else
                    {{-- Traducción --}}
                    <div style="
                        font-size: 1.5rem;
                        font-weight: 500;
                        color: var(--color-text);
                        margin-bottom: 1.25rem;
                        padding: 0.75rem 1.5rem;
                        background: rgba(124,110,245,0.08);
                        border-radius: 12px;
                        border: 1px solid rgba(124,110,245,0.2);
                    ">
                        {{ $card['translation'] }}
                    </div>

                    {{-- Morfemas (solo para compounds) --}}
                    @if(!empty($card['entities']))
                        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; margin-bottom: 1.5rem;">
                            @foreach($card['entities'] as $entity)
                                <div style="
                                    background: rgba(255,255,255,0.04);
                                    border: 1px solid var(--color-border);
                                    border-radius: 8px;
                                    padding: 0.35rem 0.75rem;
                                    font-size: 0.82rem;
                                    text-align: center;
                                ">
                                    <span style="color: var(--color-accent-soft); font-weight: 600;">{{ $entity['text'] }}</span>
                                    <span style="color: var(--color-text-muted); font-size: 0.7rem;"> [{{ $entity['type'] }}]</span><br>
                                    <span style="color: var(--color-text-muted);">{{ $entity['meaning'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- ── 4 BOTONES ANKI con intervalos estimados ─────────── --}}
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.6rem; width: 100%; max-width: 520px;" wire:loading.class="opacity-50">

                        {{-- Otra vez --}}
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.35rem;">
                            <span style="font-size: 0.68rem; color: #f87171; font-weight: 500; letter-spacing: 0.3px;">
                                {{ $estimatedIntervals['again'] ?? '…' }}
                            </span>
                            <button
                                wire:click="rateAgain"
                                wire:loading.attr="disabled"
                                id="btn-again"
                                style="
                                    width: 100%;
                                    padding: 0.7rem 0.4rem;
                                    background: rgba(248,113,113,0.1);
                                    color: #f87171;
                                    border: 1px solid rgba(248,113,113,0.35);
                                    border-radius: 10px;
                                    font-size: 0.82rem;
                                    font-weight: 600;
                                    cursor: pointer;
                                    font-family: inherit;
                                    transition: all 0.15s;
                                "
                                onmouseover="this.style.background='rgba(248,113,113,0.22)'; this.style.transform='translateY(-1px)';"
                                onmouseout="this.style.background='rgba(248,113,113,0.1)'; this.style.transform='none';"
                            >↩ Otra vez</button>
                        </div>

                        {{-- Difícil --}}
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.35rem;">
                            <span style="font-size: 0.68rem; color: #fbbf24; font-weight: 500; letter-spacing: 0.3px;">
                                {{ $estimatedIntervals['hard'] ?? '…' }}
                            </span>
                            <button
                                wire:click="rateHard"
                                wire:loading.attr="disabled"
                                id="btn-hard"
                                style="
                                    width: 100%;
                                    padding: 0.7rem 0.4rem;
                                    background: rgba(251,191,36,0.1);
                                    color: #fbbf24;
                                    border: 1px solid rgba(251,191,36,0.35);
                                    border-radius: 10px;
                                    font-size: 0.82rem;
                                    font-weight: 600;
                                    cursor: pointer;
                                    font-family: inherit;
                                    transition: all 0.15s;
                                "
                                onmouseover="this.style.background='rgba(251,191,36,0.22)'; this.style.transform='translateY(-1px)';"
                                onmouseout="this.style.background='rgba(251,191,36,0.1)'; this.style.transform='none';"
                            >◐ Difícil</button>
                        </div>

                        {{-- Bien --}}
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.35rem;">
                            <span style="font-size: 0.68rem; color: #34d399; font-weight: 500; letter-spacing: 0.3px;">
                                {{ $estimatedIntervals['good'] ?? '…' }}
                            </span>
                            <button
                                wire:click="rateGood"
                                wire:loading.attr="disabled"
                                id="btn-good"
                                style="
                                    width: 100%;
                                    padding: 0.7rem 0.4rem;
                                    background: rgba(52,211,153,0.1);
                                    color: #34d399;
                                    border: 1px solid rgba(52,211,153,0.35);
                                    border-radius: 10px;
                                    font-size: 0.82rem;
                                    font-weight: 600;
                                    cursor: pointer;
                                    font-family: inherit;
                                    transition: all 0.15s;
                                "
                                onmouseover="this.style.background='rgba(52,211,153,0.22)'; this.style.transform='translateY(-1px)';"
                                onmouseout="this.style.background='rgba(52,211,153,0.1)'; this.style.transform='none';"
                            >✓ Bien</button>
                        </div>

                        {{-- Fácil --}}
                        <div style="display: flex; flex-direction: column; align-items: center; gap: 0.35rem;">
                            <span style="font-size: 0.68rem; color: #818cf8; font-weight: 500; letter-spacing: 0.3px;">
                                {{ $estimatedIntervals['easy'] ?? '…' }}
                            </span>
                            <button
                                wire:click="rateEasy"
                                wire:loading.attr="disabled"
                                id="btn-easy"
                                style="
                                    width: 100%;
                                    padding: 0.7rem 0.4rem;
                                    background: rgba(99,102,241,0.1);
                                    color: #818cf8;
                                    border: 1px solid rgba(99,102,241,0.35);
                                    border-radius: 10px;
                                    font-size: 0.82rem;
                                    font-weight: 600;
                                    cursor: pointer;
                                    font-family: inherit;
                                    transition: all 0.15s;
                                "
                                onmouseover="this.style.background='rgba(99,102,241,0.22)'; this.style.transform='translateY(-1px)';"
                                onmouseout="this.style.background='rgba(99,102,241,0.1)'; this.style.transform='none';"
                            >★ Fácil</button>
                        </div>
                    </div>

                    {{-- Info SRS actual --}}
                    <p style="margin-top: 1rem; font-size: 0.75rem; color: var(--color-text-muted);">
                        Intervalo: {{ $card['interval_days'] }}d ·
                        Reps: {{ $card['repetitions'] }} ·
                        EF: {{ number_format($card['ease_factor'] ?? 2.5, 2) }}
                    </p>
                @endif
            </div>
        @endif
    @endif
</div>
