<div>
    <div class="auth-card">
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.4rem;">Bienvenido de vuelta</h2>
        <p style="color: var(--color-text-muted); font-size: 0.875rem; margin-bottom: 1.75rem;">
            Continúa tu práctica de coreano
        </p>

        <form wire:submit="login">
            {{-- Email --}}
            <div class="form-group">
                <label for="login-email">Correo electrónico</label>
                <input
                    id="login-email"
                    type="email"
                    wire:model="email"
                    class="form-control @error('email') is-invalid @enderror"
                    placeholder="tu@correo.com"
                    autocomplete="email"
                >
                @error('email')
                    <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>

            {{-- Contraseña --}}
            <div class="form-group">
                <label for="login-password">Contraseña</label>
                <input
                    id="login-password"
                    type="password"
                    wire:model="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Tu contraseña"
                    autocomplete="current-password"
                >
                @error('password')
                    <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>

            {{-- Recuérdame --}}
            <div class="form-group" style="display: flex; align-items: center; gap: 0.6rem;">
                <input
                    id="login-remember"
                    type="checkbox"
                    wire:model="remember"
                    style="width: 16px; height: 16px; accent-color: var(--color-accent);"
                >
                <label for="login-remember" style="font-size: 0.875rem; margin-bottom: 0; cursor: pointer;">
                    Mantener sesión iniciada
                </label>
            </div>

            <button type="submit" class="btn-submit" wire:loading.attr="disabled">
                <span wire:loading.remove>Iniciar sesión</span>
                <span wire:loading>Iniciando sesión...</span>
            </button>
        </form>
    </div>

    <div class="auth-footer">
        ¿No tienes cuenta? <a href="{{ route('register') }}" wire:navigate>Regístrate gratis</a>
    </div>
</div>
