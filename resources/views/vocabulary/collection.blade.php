@extends('layouts.app')

@section('title', 'Mi Colección — LearnKoreapp')

@section('content')
<div style="max-width: 1100px; margin: 0 auto;">

    {{-- Header --}}
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 0.25rem;">Mi colección</h1>
        <p style="color: var(--color-text-muted); font-size: 0.9rem;">
            Tu vocabulario coreano personalizado con estado de repaso SRS
        </p>
    </div>

    {{-- Añadir nueva palabra --}}
    <div style="margin-bottom: 2rem;">
        @livewire('vocabulary.add-word')
    </div>

    {{-- Colección interactiva —Fase C --}}
    @livewire('vocabulary.my-collection')

</div>
@endsection
