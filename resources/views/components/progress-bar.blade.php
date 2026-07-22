@props([
    'value'   => 0,       // progreso actual (número)
    'max'     => 100,     // valor máximo
    'label'   => '',      // etiqueta opcional
    'color'   => null,    // color de la barra (CSS var o hex), null = auto
    'height'  => '8px',
    'showPct' => false,   // mostrar porcentaje a la derecha
])
@php
    $pct      = $max > 0 ? min(100, round($value / $max * 100)) : 0;
    $barColor = $color ?? match(true) {
        $pct >= 80 => 'var(--color-success)',
        $pct >= 50 => 'var(--color-accent)',
        default    => 'var(--color-danger)',
    };
@endphp
<div>
    @if($label || $showPct)
        <div style="display:flex; justify-content:space-between; font-size:0.78rem; color:var(--color-text-muted); margin-bottom:0.3rem;">
            @if($label) <span>{{ $label }}</span> @endif
            @if($showPct) <span style="font-weight:600; color:{{ $barColor }};">{{ $pct }}%</span> @endif
        </div>
    @endif
    <div style="height:{{ $height }}; background:rgba(255,255,255,0.06); border-radius:100px; overflow:hidden;">
        <div style="
            height:100%;
            width:{{ $pct }}%;
            background:{{ $barColor }};
            border-radius:100px;
            transition:width 0.5s ease;
        "></div>
    </div>
</div>
