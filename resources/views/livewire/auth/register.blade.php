<div>
    <div class="auth-card">
        <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.4rem;">Crear cuenta</h2>
        <p style="color: var(--color-text-muted); font-size: 0.875rem; margin-bottom: 1.75rem;">
            Empieza tu viaje de aprendizaje del coreano
        </p>

        <form wire:submit="register">
            {{-- Nombre --}}
            <div class="form-group">
                <label for="reg-name">Nombre completo</label>
                <input
                    id="reg-name"
                    type="text"
                    wire:model="name"
                    class="form-control @error('name') is-invalid @enderror"
                    placeholder="Tu nombre"
                    autocomplete="name"
                >
                @error('name')
                    <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div class="form-group">
                <label for="reg-email">Correo electrónico</label>
                <input
                    id="reg-email"
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
                <label for="reg-password">Contraseña</label>
                <input
                    id="reg-password"
                    type="password"
                    wire:model="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Mínimo 8 caracteres"
                    autocomplete="new-password"
                >
                @error('password')
                    <p class="invalid-feedback">{{ $message }}</p>
                @enderror
            </div>

            {{-- Confirmar contraseña --}}
            <div class="form-group">
                <label for="reg-password-confirm">Confirmar contraseña</label>
                <input
                    id="reg-password-confirm"
                    type="password"
                    wire:model="password_confirmation"
                    class="form-control"
                    placeholder="Repite la contraseña"
                    autocomplete="new-password"
                >
            </div>

            <button type="submit" class="btn-submit" wire:loading.attr="disabled">
                <span wire:loading.remove>Crear cuenta</span>
                <span wire:loading>Creando cuenta...</span>
            </button>
        </form>
    </div>

    <div class="auth-footer">
        ¿Ya tienes cuenta? <a href="{{ route('login') }}" wire:navigate>Inicia sesión</a>
    </div>
</div>
