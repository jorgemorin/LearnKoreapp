@extends('layouts.app')

@section('title', 'Sesión de Repaso — LearnKoreapp')

@section('content')
<div style="max-width: 700px; margin: 0 auto;">

    {{-- Header --}}
    <div style="text-align: center; margin-bottom: 2.5rem;">
        <h1 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 0.3rem;">Sesión de repaso</h1>
        <p style="color: var(--color-text-muted); font-size: 0.9rem;">
            Repasa las tarjetas vencidas con el sistema SM-2
        </p>
    </div>

    @livewire('study.review-session')

</div>
@endsection
