<div>
    @if($error)
        <div style="padding:1rem 1.25rem; background:rgba(248,113,113,0.12); border:1px solid rgba(248,113,113,0.25); border-radius:10px; color:var(--color-danger);">
            {{ $error }}
        </div>
    @elseif($loading)
        <div style="text-align:center; padding:3rem; color:var(--color-text-muted);">Cargando estadísticas...</div>
    @else

        {{-- ─────────────── KPIs principales ─────────────── --}}
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); gap:1rem; margin-bottom:2rem;">

            {{-- Precisión global --}}
            <div style="background:var(--color-bg-card); border:1px solid var(--color-border); border-radius:16px; padding:1.5rem; text-align:center;">
                <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:1px; color:var(--color-text-muted); margin-bottom:0.5rem;">Precisión global</div>
                <div style="font-size:2.5rem; font-weight:700; color:{{ ($stats['global_accuracy'] ?? 0) >= 80 ? 'var(--color-success)' : (($stats['global_accuracy'] ?? 0) >= 60 ? 'var(--color-accent-soft)' : 'var(--color-danger)') }};">
                    {{ $stats['global_accuracy'] !== null ? $stats['global_accuracy'] . '%' : '—' }}
                </div>
            </div>

            {{-- Total estudiadas --}}
            <div style="background:var(--color-bg-card); border:1px solid var(--color-border); border-radius:16px; padding:1.5rem; text-align:center;">
                <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:1px; color:var(--color-text-muted); margin-bottom:0.5rem;">Respuestas totales</div>
                <div style="font-size:2.5rem; font-weight:700; color:var(--color-accent-soft);">{{ $stats['total_studied'] }}</div>
            </div>

            {{-- Pendientes hoy --}}
            <div style="background:var(--color-bg-card); border:1px solid var(--color-border); border-radius:16px; padding:1.5rem; text-align:center;">
                <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:1px; color:var(--color-text-muted); margin-bottom:0.5rem;">Pendientes hoy</div>
                <div style="font-size:2.5rem; font-weight:700; color:{{ ($stats['due_today'] ?? 0) > 0 ? 'var(--color-warning)' : 'var(--color-success)' }};">
                    {{ $stats['due_today'] }}
                </div>
            </div>

            {{-- En colección --}}
            <div style="background:var(--color-bg-card); border:1px solid var(--color-border); border-radius:16px; padding:1.5rem; text-align:center;">
                <div style="font-size:0.72rem; text-transform:uppercase; letter-spacing:1px; color:var(--color-text-muted); margin-bottom:0.5rem;">En colección</div>
                <div style="font-size:2.5rem; font-weight:700; color:var(--color-text);">{{ $stats['total_in_collection'] }}</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.25rem; margin-bottom:2rem;">

            {{-- ─────────────── Precisión por Tag ─────────────── --}}
            <div style="background:var(--color-bg-card); border:1px solid var(--color-border); border-radius:16px; padding:1.5rem;">
                <h3 style="font-size:0.9rem; font-weight:600; margin-bottom:1.25rem; color:var(--color-accent-soft);">📑 Precisión por etiqueta</h3>
                @if(empty($stats['accuracy_by_tag']))
                    <p style="color:var(--color-text-muted); font-size:0.85rem;">Sin datos de estudio todavía.</p>
                @else
                    <div style="display:grid; gap:0.6rem;">
                        @foreach($stats['accuracy_by_tag'] as $row)
                            @php $acc = (float)($row->tasa_acierto ?? $row['tasa_acierto'] ?? 0); @endphp
                            <div>
                                <div style="display:flex; justify-content:space-between; font-size:0.82rem; margin-bottom:0.2rem;">
                                    <span>{{ $row->tag_name ?? $row['tag_name'] }}</span>
                                    <span style="font-weight:600; color:{{ $acc >= 80 ? 'var(--color-success)' : ($acc >= 60 ? 'var(--color-accent-soft)' : 'var(--color-danger)') }};">{{ $acc }}%</span>
                                </div>
                                <div style="height:5px; background:rgba(255,255,255,0.06); border-radius:100px; overflow:hidden;">
                                    <div style="height:100%; width:{{ $acc }}%; background:{{ $acc >= 80 ? 'var(--color-success)' : ($acc >= 60 ? 'var(--color-accent)' : 'var(--color-danger)') }}; border-radius:100px;"></div>
                                </div>
                                <div style="font-size:0.7rem; color:var(--color-text-muted);">{{ $row->intentos ?? $row['intentos'] }} intentos</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- ─────────────── Precisión por Tipo Morfológico ─────────────── --}}
            <div style="background:var(--color-bg-card); border:1px solid var(--color-border); border-radius:16px; padding:1.5rem;">
                <h3 style="font-size:0.9rem; font-weight:600; margin-bottom:1.25rem; color:var(--color-accent-soft);">🔤 Precisión por tipo morfológico</h3>
                @if(empty($stats['accuracy_by_type']))
                    <p style="color:var(--color-text-muted); font-size:0.85rem;">Estudia morfemas individuales para ver datos.</p>
                @else
                    <div style="display:grid; gap:0.6rem;">
                        @foreach($stats['accuracy_by_type'] as $row)
                            @php $acc = (float)($row->tasa_acierto ?? $row['tasa_acierto'] ?? 0); @endphp
                            <div>
                                <div style="display:flex; justify-content:space-between; font-size:0.82rem; margin-bottom:0.2rem;">
                                    <span style="text-transform:capitalize;">{{ $row->type ?? $row['type'] }}</span>
                                    <span style="font-weight:600; color:{{ $acc >= 80 ? 'var(--color-success)' : ($acc >= 60 ? 'var(--color-accent-soft)' : 'var(--color-danger)') }};">{{ $acc }}%</span>
                                </div>
                                <div style="height:5px; background:rgba(255,255,255,0.06); border-radius:100px; overflow:hidden;">
                                    <div style="height:100%; width:{{ $acc }}%; background:{{ $acc >= 80 ? 'var(--color-success)' : ($acc >= 60 ? 'var(--color-accent)' : 'var(--color-danger)') }}; border-radius:100px;"></div>
                                </div>
                                <div style="font-size:0.7rem; color:var(--color-text-muted);">{{ $row->intentos ?? $row['intentos'] }} intentos</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- ─────────────── Actividad reciente (últimos 7 días) ─────────────── --}}
        <div style="background:var(--color-bg-card); border:1px solid var(--color-border); border-radius:16px; padding:1.5rem;">
            <h3 style="font-size:0.9rem; font-weight:600; margin-bottom:1.25rem; color:var(--color-accent-soft);">📅 Actividad reciente (últimos 7 días)</h3>
            @if(empty($stats['recent_sessions']))
                <p style="color:var(--color-text-muted); font-size:0.85rem; text-align:center; padding:1rem 0;">
                    No hay actividad en los últimos 7 días.
                    <a href="{{ route('study') }}" style="color:var(--color-accent-soft);">¡Empieza a repasar!</a>
                </p>
            @else
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(110px, 1fr)); gap:0.75rem;">
                    @foreach($stats['recent_sessions'] as $session)
                        @php
                            $acc = (float)($session->tasa_acierto ?? $session['tasa_acierto'] ?? 0);
                            $date = $session->date ?? $session['date'];
                        @endphp
                        <div style="background:rgba(255,255,255,0.04); border:1px solid var(--color-border); border-radius:10px; padding:0.75rem; text-align:center;">
                            <div style="font-size:0.7rem; color:var(--color-text-muted); margin-bottom:0.3rem;">{{ \Carbon\Carbon::parse($date)->format('d M') }}</div>
                            <div style="font-size:1.5rem; font-weight:700; color:{{ $acc >= 80 ? 'var(--color-success)' : ($acc >= 60 ? 'var(--color-accent-soft)' : 'var(--color-danger)') }};">{{ $acc }}%</div>
                            <div style="font-size:0.7rem; color:var(--color-text-muted);">{{ $session->intentos ?? $session['intentos'] }} reps</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- CTA si no hay datos --}}
        @if($stats['total_studied'] === 0)
            <div style="margin-top:1.5rem; text-align:center; padding:2rem; background:rgba(124,110,245,0.06); border:1px solid rgba(124,110,245,0.2); border-radius:16px;">
                <div style="font-size:2.5rem; margin-bottom:0.75rem;">📊</div>
                <p style="color:var(--color-text-muted); margin-bottom:1rem;">Aún no tienes estadísticas. ¡Añade vocabulario y empieza a repasar!</p>
                <div style="display:flex; gap:0.75rem; justify-content:center; flex-wrap:wrap;">
                    <a href="{{ route('collection') }}" style="padding:0.65rem 1.25rem; background:var(--color-accent); color:#fff; border-radius:10px; text-decoration:none; font-size:0.875rem; font-weight:600;">+ Añadir vocabulario</a>
                    <a href="{{ route('study') }}" style="padding:0.65rem 1.25rem; background:rgba(255,255,255,0.06); color:var(--color-text-muted); border:1px solid var(--color-border); border-radius:10px; text-decoration:none; font-size:0.875rem;">Repasar ahora</a>
                </div>
            </div>
        @endif
    @endif
</div>
