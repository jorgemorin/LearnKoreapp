@props([
    'entities'   => collect(),   // Collection de Entity models (con pivot position_order)
    'compact'    => false,       // Vista compacta (solo texto + tipo, sin meaning)
])
<div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
    @forelse($entities->sortBy(fn($e) => $e->pivot?->position_order ?? 0) as $entity)
        @php
            $typeColors = [
                'root'     => ['bg' => 'rgba(124,110,245,0.12)', 'border' => 'rgba(124,110,245,0.3)', 'color' => 'var(--color-accent-soft)'],
                'particle' => ['bg' => 'rgba(251,191,36,0.1)',   'border' => 'rgba(251,191,36,0.3)',  'color' => 'var(--color-warning)'],
                'word'     => ['bg' => 'rgba(52,211,153,0.1)',   'border' => 'rgba(52,211,153,0.3)',  'color' => 'var(--color-success)'],
            ];
            $c = $typeColors[$entity->type] ?? $typeColors['word'];
        @endphp
        <div style="
            background: {{ $c['bg'] }};
            border: 1px solid {{ $c['border'] }};
            border-radius: 10px;
            padding: {{ $compact ? '0.3rem 0.65rem' : '0.5rem 0.85rem' }};
            text-align: center;
        ">
            <div style="font-size: {{ $compact ? '1rem' : '1.25rem' }}; font-weight: 700; color: {{ $c['color'] }}; letter-spacing: 0.5px;">
                {{ $entity->text }}
            </div>
            <div style="font-size: 0.65rem; color: {{ $c['color'] }}; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 0.1rem;">
                {{ $entity->type }}
            </div>
            @if(! $compact && $entity->meaning)
                <div style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 0.25rem;">
                    {{ $entity->meaning }}
                </div>
            @endif
        </div>
    @empty
        <span style="color: var(--color-text-muted); font-size: 0.85rem; font-style: italic;">Sin morfemas registrados</span>
    @endforelse
</div>
