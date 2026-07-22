@extends('layouts.app')

@section('title', 'Panel Principal — LearnKoreapp')

@section('content')
@php
    $userId     = auth()->id();
    $dueToday   = \App\Models\UserProgress::where('user_id', $userId)->whereDate('next_review_date', '<=', today())->count();
    $collection = \App\Models\UserProgress::where('user_id', $userId)->count();
    $accuracy   = \App\Models\StudyLog::where('user_id', $userId)
        ->selectRaw('ROUND(100.0 * SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 0) as acc')
        ->value('acc');
    $todayStudied = \App\Models\StudyLog::where('user_id', $userId)->whereDate('created_at', today())->count();
@endphp
<div style="max-width: 860px; margin: 0 auto;">

    {{-- Bienvenida ─────────────────────────────────────────────────────────── --}}
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.3rem;">
            ¡Hola, {{ auth()->user()->name }}! 👋
        </h1>
        <p style="color: var(--color-text-muted);">
            {{ $dueToday > 0
                ? "Tienes {$dueToday} tarjeta" . ($dueToday > 1 ? 's' : '') . " pendiente" . ($dueToday > 1 ? 's' : '') . " de repaso hoy."
                : "¡Estás al día! No tienes tarjetas pendientes hoy. 🎉" }}
        </p>
    </div>

    {{-- KPIs ──────────────────────────────────────────────────────────────── --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1.25rem; margin-bottom: 2rem;">

        <div class="card" style="text-align:center; padding:1.75rem; position:relative; overflow:hidden;">
            <div style="font-size:2.2rem; margin-bottom:0.4rem;">📚</div>
            <div style="font-size:2.2rem; font-weight:700; color:var(--color-accent-soft);">{{ $collection }}</div>
            <div style="color:var(--color-text-muted); font-size:0.82rem;">Palabras en colección</div>
            <a href="{{ route('collection') }}" style="position:absolute; inset:0; opacity:0;"></a>
        </div>

        <div class="card" style="text-align:center; padding:1.75rem; position:relative; overflow:hidden; {{ $dueToday > 0 ? 'border-color: rgba(251,191,36,0.4)' : '' }}">
            <div style="font-size:2.2rem; margin-bottom:0.4rem;">🔄</div>
            <div style="font-size:2.2rem; font-weight:700; color:{{ $dueToday > 0 ? 'var(--color-warning)' : 'var(--color-success)' }};">{{ $dueToday }}</div>
            <div style="color:var(--color-text-muted); font-size:0.82rem;">Pendientes hoy</div>
            <a href="{{ route('study') }}" style="position:absolute; inset:0; opacity:0;"></a>
        </div>

        <div class="card" style="text-align:center; padding:1.75rem;">
            <div style="font-size:2.2rem; margin-bottom:0.4rem;">🎯</div>
            <div style="font-size:2.2rem; font-weight:700; color:{{ $accuracy >= 80 ? 'var(--color-success)' : ($accuracy >= 60 ? 'var(--color-accent-soft)' : 'var(--color-danger)') }};">
                {{ $accuracy !== null ? $accuracy . '%' : '—' }}
            </div>
            <div style="color:var(--color-text-muted); font-size:0.82rem;">Tasa de acierto</div>
        </div>

        <div class="card" style="text-align:center; padding:1.75rem;">
            <div style="font-size:2.2rem; margin-bottom:0.4rem;">📖</div>
            <div style="font-size:2.2rem; font-weight:700; color:var(--color-text);">{{ $todayStudied }}</div>
            <div style="color:var(--color-text-muted); font-size:0.82rem;">Repasos hoy</div>
        </div>
    </div>

    {{-- CTA principal: repasar ahora si hay pendientes ─────────────────────── --}}
    @if($dueToday > 0)
        <div style="
            background: linear-gradient(135deg, rgba(124,110,245,0.15) 0%, rgba(99,87,207,0.08) 100%);
            border: 1px solid rgba(124,110,245,0.35);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        ">
            <div>
                <div style="font-size:1.5rem; font-weight:700; margin-bottom:0.3rem;">
                    {{ $dueToday }} tarjeta{{ $dueToday > 1 ? 's' : '' }} esperan tu repaso
                </div>
                <p style="color:var(--color-text-muted); font-size:0.875rem; margin:0;">
                    El sistema SM-2 ha seleccionado las tarjetas óptimas para hoy.
                </p>
            </div>
            <a href="{{ route('study') }}" style="
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.9rem 2rem;
                background: var(--color-accent);
                color: #fff;
                border-radius: 12px;
                text-decoration: none;
                font-weight: 700;
                font-size: 1rem;
                white-space: nowrap;
                transition: all 0.2s;
            " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 24px rgba(124,110,245,0.4)';"
               onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
                🃏 Repasar ahora
            </a>
        </div>
    @endif

    {{-- Acciones rápidas ──────────────────────────────────────────────────── --}}
    <div class="card">
        <h2 style="font-size:1rem; font-weight:600; margin-bottom:1.25rem; color:var(--color-text-muted); text-transform:uppercase; letter-spacing:0.5px; font-size:0.75rem;">Acceso rápido</h2>
        <div style="display:flex; flex-wrap:wrap; gap:0.75rem;">
            <a href="{{ route('collection') }}" style="
                display:flex; align-items:center; gap:0.5rem;
                padding:0.65rem 1.25rem;
                background:rgba(124,110,245,0.12); border:1px solid rgba(124,110,245,0.25); border-radius:10px;
                color:var(--color-accent-soft); text-decoration:none; font-size:0.875rem; font-weight:500;
                transition:all 0.2s;
            " onmouseover="this.style.background='rgba(124,110,245,0.2)'"
               onmouseout="this.style.background='rgba(124,110,245,0.12)'">
                ➕ Añadir vocabulario
            </a>
            <a href="{{ route('study') }}" style="
                display:flex; align-items:center; gap:0.5rem;
                padding:0.65rem 1.25rem;
                background:rgba(255,255,255,0.05); border:1px solid var(--color-border); border-radius:10px;
                color:var(--color-text); text-decoration:none; font-size:0.875rem; font-weight:500;
                transition:all 0.2s;
            " onmouseover="this.style.background='rgba(255,255,255,0.09)'"
               onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                🃏 Sesión de repaso
            </a>
            <a href="{{ route('stats') }}" style="
                display:flex; align-items:center; gap:0.5rem;
                padding:0.65rem 1.25rem;
                background:rgba(255,255,255,0.05); border:1px solid var(--color-border); border-radius:10px;
                color:var(--color-text); text-decoration:none; font-size:0.875rem; font-weight:500;
                transition:all 0.2s;
            " onmouseover="this.style.background='rgba(255,255,255,0.09)'"
               onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                📊 Mis estadísticas
            </a>
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" style="
                    display:flex; align-items:center; gap:0.5rem;
                    padding:0.65rem 1.25rem;
                    background:rgba(251,191,36,0.1); border:1px solid rgba(251,191,36,0.25); border-radius:10px;
                    color:var(--color-warning); text-decoration:none; font-size:0.875rem; font-weight:500;
                    transition:all 0.2s;
                " onmouseover="this.style.background='rgba(251,191,36,0.2)'"
                   onmouseout="this.style.background='rgba(251,191,36,0.1)'">
                    ⚙️ Panel admin
                </a>
            @endif
        </div>
    </div>

</div>
@endsection
