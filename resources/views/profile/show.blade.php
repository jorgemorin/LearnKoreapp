@extends('layouts.app')

@section('title', 'Mi Perfil — LearnKoreapp')

@section('content')
<div style="max-width: 640px; margin: 0 auto;">

    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 1.8rem; font-weight: 700; margin-bottom: 0.3rem;">Mi Perfil</h1>
        <p style="color: var(--color-text-muted); font-size: 0.9rem;">Gestiona tus datos personales y credenciales.</p>
    </div>

    {{-- Flash success --}}
    @if(session('success'))
        <div style="
            padding:0.85rem 1.25rem; border-radius:10px; margin-bottom:1.5rem;
            background:rgba(52,211,153,0.12); border:1px solid rgba(52,211,153,0.25);
            color:var(--color-success); font-size:0.875rem; display:flex; align-items:center; gap:0.5rem;
        ">✅ {{ session('success') }}</div>
    @endif

    {{-- ── Datos personales ────────────────────────────────────────────────── --}}
    <div class="card" style="margin-bottom:1.5rem;">
        <h2 style="font-size:1rem; font-weight:600; margin-bottom:1.25rem;">Datos personales</h2>
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PUT')

            <div style="display:grid; gap:1rem;">
                {{-- Nombre --}}
                <div>
                    <label style="display:block; font-size:0.82rem; color:var(--color-text-muted); margin-bottom:0.35rem;">Nombre</label>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        style="width:100%; padding:0.7rem 0.9rem; background:rgba(255,255,255,0.04); border:1px solid {{ $errors->has('name') ? 'var(--color-danger)' : 'var(--color-border)' }}; border-radius:8px; color:var(--color-text); font-size:0.9rem; font-family:inherit; outline:none; box-sizing:border-box;"
                    >
                    @error('name')
                        <p style="color:var(--color-danger); font-size:0.75rem; margin-top:0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label style="display:block; font-size:0.82rem; color:var(--color-text-muted); margin-bottom:0.35rem;">Correo electrónico</label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        style="width:100%; padding:0.7rem 0.9rem; background:rgba(255,255,255,0.04); border:1px solid {{ $errors->has('email') ? 'var(--color-danger)' : 'var(--color-border)' }}; border-radius:8px; color:var(--color-text); font-size:0.9rem; font-family:inherit; outline:none; box-sizing:border-box;"
                    >
                    @error('email')
                        <p style="color:var(--color-danger); font-size:0.75rem; margin-top:0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Rol (solo lectura) --}}
                <div>
                    <label style="display:block; font-size:0.82rem; color:var(--color-text-muted); margin-bottom:0.35rem;">Rol</label>
                    <div style="padding:0.7rem 0.9rem; background:rgba(255,255,255,0.02); border:1px solid var(--color-border); border-radius:8px; color:var(--color-text-muted); font-size:0.9rem; display:flex; align-items:center; gap:0.5rem;">
                        {{ $user->role === 'admin' ? '⚙️ Administrador' : '👤 Usuario' }}
                    </div>
                </div>

                <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                    <button type="submit" style="padding:0.65rem 1.5rem; background:var(--color-accent); color:#fff; border:none; border-radius:8px; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:inherit; transition:all 0.2s;">
                        💾 Guardar cambios
                    </button>
                    <a href="{{ route('dashboard') }}" style="padding:0.65rem 1rem; background:rgba(255,255,255,0.05); color:var(--color-text-muted); border:1px solid var(--color-border); border-radius:8px; font-size:0.875rem; text-decoration:none; transition:all 0.2s;">
                        Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- ── Cambiar contraseña ──────────────────────────────────────────────── --}}
    <div class="card">
        <h2 style="font-size:1rem; font-weight:600; margin-bottom:1.25rem;">Cambiar contraseña</h2>
        <form method="POST" action="{{ route('profile.password') }}">
            @csrf
            @method('PUT')

            <div style="display:grid; gap:1rem;">
                <div>
                    <label style="display:block; font-size:0.82rem; color:var(--color-text-muted); margin-bottom:0.35rem;">Contraseña actual</label>
                    <input
                        type="password"
                        name="current_password"
                        style="width:100%; padding:0.7rem 0.9rem; background:rgba(255,255,255,0.04); border:1px solid {{ $errors->has('current_password') ? 'var(--color-danger)' : 'var(--color-border)' }}; border-radius:8px; color:var(--color-text); font-size:0.9rem; font-family:inherit; outline:none; box-sizing:border-box;"
                    >
                    @error('current_password')
                        <p style="color:var(--color-danger); font-size:0.75rem; margin-top:0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label style="display:block; font-size:0.82rem; color:var(--color-text-muted); margin-bottom:0.35rem;">Nueva contraseña</label>
                    <input
                        type="password"
                        name="password"
                        style="width:100%; padding:0.7rem 0.9rem; background:rgba(255,255,255,0.04); border:1px solid {{ $errors->has('password') ? 'var(--color-danger)' : 'var(--color-border)' }}; border-radius:8px; color:var(--color-text); font-size:0.9rem; font-family:inherit; outline:none; box-sizing:border-box;"
                    >
                    @error('password')
                        <p style="color:var(--color-danger); font-size:0.75rem; margin-top:0.25rem;">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label style="display:block; font-size:0.82rem; color:var(--color-text-muted); margin-bottom:0.35rem;">Confirmar nueva contraseña</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        style="width:100%; padding:0.7rem 0.9rem; background:rgba(255,255,255,0.04); border:1px solid var(--color-border); border-radius:8px; color:var(--color-text); font-size:0.9rem; font-family:inherit; outline:none; box-sizing:border-box;"
                    >
                </div>

                <div>
                    <button type="submit" style="padding:0.65rem 1.5rem; background:rgba(248,113,113,0.15); color:var(--color-danger); border:1px solid rgba(248,113,113,0.3); border-radius:8px; font-size:0.875rem; font-weight:600; cursor:pointer; font-family:inherit; transition:all 0.2s;">
                        🔑 Cambiar contraseña
                    </button>
                </div>
            </div>
        </form>
    </div>

</div>
@endsection
