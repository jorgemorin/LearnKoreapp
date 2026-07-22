@extends('layouts.app')

@section('title', 'Mis Estadísticas — LearnKoreapp')

@section('content')
<div style="max-width: 900px; margin: 0 auto;">

    <div style="text-align:center; margin-bottom:2rem;">
        <h1 style="font-size:1.8rem; font-weight:700; margin-bottom:0.3rem;">Mis Estadísticas</h1>
        <p style="color:var(--color-text-muted); font-size:0.9rem;">
            Rendimiento personal · Análisis morfológico y semántico
        </p>
    </div>

    @livewire('stats.personal-dashboard')

</div>
@endsection
