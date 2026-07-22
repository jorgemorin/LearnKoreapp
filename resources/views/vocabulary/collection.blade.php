@extends('layouts.app')

@section('title', 'Mi Colección — LearnKoreapp')

@section('content')
<div style="max-width: 900px; margin: 0 auto;">

    {{-- Header --}}
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 0.25rem;">Mi colección</h1>
        <p style="color: var(--color-text-muted); font-size: 0.9rem;">
            Tu vocabulario coreano personalizado con estado de repaso SRS
        </p>
    </div>

    {{-- Añadir nueva palabra --}}
    <div style="margin-bottom: 2.5rem;">
        @livewire('vocabulary.add-word')
    </div>

    {{-- Lista de palabras --}}
    <div>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
            <h2 style="font-size: 1.1rem; font-weight: 600;">Palabras guardadas</h2>
            <span style="color: var(--color-text-muted); font-size: 0.875rem;">{{ $total }} palabras</span>
        </div>

        @if($progress->isEmpty())
            <div style="
                text-align: center;
                padding: 3rem 1rem;
                background: var(--color-bg-card);
                border: 1px solid var(--color-border);
                border-radius: 20px;
                color: var(--color-text-muted);
            ">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📚</div>
                <p style="font-size: 1rem; margin-bottom: 0.5rem;">Tu colección está vacía</p>
                <p style="font-size: 0.875rem;">Añade tu primera palabra coreana usando el formulario de arriba</p>
            </div>
        @else
            <div style="display: grid; gap: 0.75rem;">
                @foreach($progress as $item)
                    @php $compound = $item->item; @endphp
                    @if($compound)
                    <div style="
                        background: var(--color-bg-card);
                        border: 1px solid var(--color-border);
                        border-radius: 14px;
                        padding: 1.25rem 1.5rem;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        transition: border-color 0.2s;
                    " onmouseover="this.style.borderColor='rgba(124,110,245,0.4)'" onmouseout="this.style.borderColor='var(--color-border)'">

                        <div style="flex: 1;">
                            <div style="display: flex; align-items: baseline; gap: 0.75rem; margin-bottom: 0.4rem;">
                                <span style="font-size: 1.6rem; font-weight: 700; color: var(--color-text);">{{ $compound->full_text }}</span>
                                <span style="color: var(--color-text-muted); font-size: 0.9rem;">{{ $compound->translation }}</span>
                            </div>

                            {{-- Tags --}}
                            @if($compound->tags->isNotEmpty())
                                <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                    @foreach($compound->tags as $tag)
                                        <span style="
                                            background: rgba(124,110,245,0.12);
                                            border: 1px solid rgba(124,110,245,0.25);
                                            border-radius: 20px;
                                            padding: 0.15rem 0.6rem;
                                            font-size: 0.72rem;
                                            color: var(--color-accent-soft);
                                        ">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Info SRS --}}
                        <div style="text-align: right; margin-left: 1.5rem; flex-shrink: 0;">
                            @php
                                $dueDate  = $item->next_review_date;
                                $isToday  = $dueDate->isToday();
                                $isPast   = $dueDate->isPast() && !$isToday;
                            @endphp
                            <div style="
                                font-size: 0.78rem;
                                font-weight: 600;
                                color: {{ $isPast ? 'var(--color-danger)' : ($isToday ? 'var(--color-success)' : 'var(--color-text-muted)') }};
                                margin-bottom: 0.25rem;
                            ">
                                @if($isPast) ⚠️ Vencida
                                @elseif($isToday) 🎯 Hoy
                                @else 📅 {{ $dueDate->diffForHumans() }}
                                @endif
                            </div>
                            <div style="font-size: 0.72rem; color: var(--color-text-muted);">
                                Intervalo: {{ $item->interval_days }}d · Reps: {{ $item->repetitions }}
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>

            {{-- Paginación --}}
            @if($progress->hasPages())
                <div style="margin-top: 1.5rem; display: flex; justify-content: center; gap: 0.5rem;">
                    @if($progress->onFirstPage())
                        <span style="padding: 0.4rem 0.85rem; border-radius: 8px; background: rgba(255,255,255,0.04); color: var(--color-text-muted); font-size: 0.85rem;">← Anterior</span>
                    @else
                        <a href="{{ $progress->previousPageUrl() }}" style="padding: 0.4rem 0.85rem; border-radius: 8px; background: var(--color-accent); color: #fff; font-size: 0.85rem; text-decoration: none;">← Anterior</a>
                    @endif

                    <span style="padding: 0.4rem 0.85rem; border-radius: 8px; background: rgba(255,255,255,0.04); color: var(--color-text-muted); font-size: 0.85rem;">
                        {{ $progress->currentPage() }} / {{ $progress->lastPage() }}
                    </span>

                    @if($progress->hasMorePages())
                        <a href="{{ $progress->nextPageUrl() }}" style="padding: 0.4rem 0.85rem; border-radius: 8px; background: var(--color-accent); color: #fff; font-size: 0.85rem; text-decoration: none;">Siguiente →</a>
                    @else
                        <span style="padding: 0.4rem 0.85rem; border-radius: 8px; background: rgba(255,255,255,0.04); color: var(--color-text-muted); font-size: 0.85rem;">Siguiente →</span>
                    @endif
                </div>
            @endif
        @endif
    </div>

</div>
@endsection
