@props([
    'accuracy' => null,  // float 0-100 o null
    'size'     => 'md',  // sm | md | lg
])
@php
    $acc    = $accuracy !== null ? (float) $accuracy : null;
    $color  = match(true) {
        $acc === null         => 'var(--color-text-muted)',
        $acc >= 80            => 'var(--color-success)',
        $acc >= 60            => 'var(--color-accent-soft)',
        default               => 'var(--color-danger)',
    };
    $bg     = match(true) {
        $acc === null         => 'rgba(255,255,255,0.05)',
        $acc >= 80            => 'rgba(52,211,153,0.12)',
        $acc >= 60            => 'rgba(124,110,245,0.12)',
        default               => 'rgba(248,113,113,0.12)',
    };
    $border = match(true) {
        $acc === null         => 'rgba(255,255,255,0.1)',
        $acc >= 80            => 'rgba(52,211,153,0.3)',
        $acc >= 60            => 'rgba(124,110,245,0.3)',
        default               => 'rgba(248,113,113,0.3)',
    };
    $padding = match($size) { 'sm' => '0.15rem 0.5rem', 'lg' => '0.4rem 1rem', default => '0.25rem 0.65rem' };
    $fsize   = match($size) { 'sm' => '0.7rem', 'lg' => '1rem', default => '0.82rem' };
@endphp
<span style="
    display: inline-flex;
    align-items: center;
    padding: {{ $padding }};
    background: {{ $bg }};
    border: 1px solid {{ $border }};
    border-radius: 100px;
    color: {{ $color }};
    font-size: {{ $fsize }};
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
">{{ $acc !== null ? round($acc) . '%' : '—' }}</span>
