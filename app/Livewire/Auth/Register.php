<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * Componente Livewire de registro de usuario.
 * Valida los datos del formulario y crea el nuevo usuario con rol 'user'.
 */
class Register extends Component
{
    public string $name     = '';
    public string $email    = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:150'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required'     => 'El nombre es obligatorio.',
            'name.max'          => 'El nombre no puede superar 150 caracteres.',
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'Introduce un correo electrónico válido.',
            'email.unique'      => 'Este correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed'=> 'Las contraseñas no coinciden.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }

    /** Procesa el registro del nuevo usuario. */
    public function register(): void
    {
        $validated = $this->validate();

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => User::ROLE_USER,
        ]);

        event(new Registered($user));

        Auth::login($user);

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register')
               ->layout('layouts.auth', ['title' => 'Crear cuenta']);
    }
}
