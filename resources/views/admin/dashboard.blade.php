@extends('layouts.app')

@section('title', 'Panel de Administración — LearnKoreapp')

@section('content')
<div style="max-width: 960px; margin: 0 auto;">

    {{-- Header --}}
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 0.3rem; color: var(--color-warning);">
            ⚙️ Panel de Administración
        </h1>
        <p style="color: var(--color-text-muted); font-size: 0.9rem;">
            Curación del Diccionario Global · Aprobación de términos IA
        </p>
    </div>

    {{-- Stats rápidas --}}
    @php
        $pending  = \App\Models\Compound::where('status', 'pending_review')->count();
        $verified = \App\Models\Compound::where('status', 'verified')->count();
        $users    = \App\Models\User::where('role', 'user')->count();
    @endphp
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem;">
        <div class="card" style="text-align:center; padding:1.5rem;">
            <div style="font-size:2rem; margin-bottom:0.4rem;">⏳</div>
            <div style="font-size:2rem; font-weight:700; color:var(--color-warning);">{{ $pending }}</div>
            <div style="color:var(--color-text-muted); font-size:0.82rem;">Pendientes</div>
        </div>
        <div class="card" style="text-align:center; padding:1.5rem;">
            <div style="font-size:2rem; margin-bottom:0.4rem;">✅</div>
            <div style="font-size:2rem; font-weight:700; color:var(--color-success);">{{ $verified }}</div>
            <div style="color:var(--color-text-muted); font-size:0.82rem;">Verificados</div>
        </div>
        <div class="card" style="text-align:center; padding:1.5rem;">
            <div style="font-size:2rem; margin-bottom:0.4rem;">👥</div>
            <div style="font-size:2rem; font-weight:700; color:var(--color-accent-soft);">{{ $users }}</div>
            <div style="color:var(--color-text-muted); font-size:0.82rem;">Usuarios</div>
        </div>
    </div>

    {{-- Cola de revisión --}}
    <div class="card">
        @livewire('admin.pending-queue')
    </div>

</div>
@endsection
