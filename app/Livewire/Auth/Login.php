<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Componente Livewire de inicio de sesión.
 * Valida credenciales, gestiona intentos fallidos y redirige al dashboard.
 */
class Login extends Component
{
    public string $email    = '';
    public string $password = '';
    public bool   $remember = false;

    protected function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'Introduce un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }

    /** Procesa el inicio de sesión. */
    public function login(): void
    {
        $validated = $this->validate();

        // Intento de autenticación con opción de "recuérdame"
        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']], $this->remember)) {
            $this->addError('email', 'Las credenciales no son correctas.');
            return;
        }

        // Verificar que la cuenta está activa
        if (! Auth::user()->is_active) {
            Auth::logout();
            $this->addError('email', 'Tu cuenta ha sido desactivada. Contacta con el administrador.');
            return;
        }

        // Regenera el ID de sesión por seguridad (solo si la sesión está disponible)
        if (request()->hasSession()) {
            request()->session()->regenerate();
        }

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login')
               ->layout('layouts.auth', ['title' => 'Iniciar sesión']);
    }
}
