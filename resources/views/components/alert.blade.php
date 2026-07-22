@props([
    'type'    => 'info',    // info | success | danger | warning
    'dismiss' => false,     // mostrar botón de cierre
])
@php
    $styles = [
        'info'    => ['bg'=>'rgba(124,110,245,0.12)', 'border'=>'rgba(124,110,245,0.3)',  'color'=>'var(--color-accent-soft)', 'icon'=>'ℹ️'],
        'success' => ['bg'=>'rgba(52,211,153,0.12)',  'border'=>'rgba(52,211,153,0.3)',   'color'=>'var(--color-success)',    'icon'=>'✅'],
        'danger'  => ['bg'=>'rgba(248,113,113,0.12)', 'border'=>'rgba(248,113,113,0.3)',  'color'=>'var(--color-danger)',     'icon'=>'❌'],
        'warning' => ['bg'=>'rgba(251,191,36,0.1)',   'border'=>'rgba(251,191,36,0.3)',   'color'=>'var(--color-warning)',    'icon'=>'⚠️'],
    ];
    $s = $styles[$type] ?? $styles['info'];
@endphp
<div
    x-data="{{ $dismiss ? '{ visible: true }' : '{}' }}"
    @if($dismiss) x-show="visible" @endif
    style="
        display:flex; align-items:flex-start; gap:0.75rem;
        padding:0.85rem 1.1rem;
        background:{{ $s['bg'] }};
        border:1px solid {{ $s['border'] }};
        border-radius:10px;
        color:{{ $s['color'] }};
        font-size:0.875rem;
    "
>
    <span style="flex-shrink:0; font-size:1rem;">{{ $s['icon'] }}</span>
    <span style="flex:1;">{{ $slot }}</span>
    @if($dismiss)
        <button @click="visible = false" style="
            background:none; border:none; cursor:pointer;
            color:{{ $s['color'] }}; opacity:0.7; font-size:1rem; padding:0; line-height:1;
        ">✕</button>
    @endif
</div>
