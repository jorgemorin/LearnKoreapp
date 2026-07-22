<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Perfil del usuario: edición de datos básicos y cambio de contraseña.
 */
class ProfileController extends Controller
{
    /**
     * GET /profile
     */
    public function show(): View
    {
        return view('profile.show', ['user' => auth()->user()]);
    }

    /**
     * PUT /profile
     * Actualiza nombre y email del usuario.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . auth()->id()],
        ], [
            'name.required'  => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email'    => 'El correo electrónico no tiene un formato válido.',
            'email.unique'   => 'Este correo electrónico ya está en uso.',
        ]);

        auth()->user()->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    /**
     * PUT /profile/password
     * Cambia la contraseña del usuario.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password'      => ['required'],
            'password'              => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'Debes introducir tu contraseña actual.',
            'password.required'         => 'La nueva contraseña es obligatoria.',
            'password.confirmed'        => 'La confirmación de contraseña no coincide.',
            'password.min'              => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        if (! Hash::check($request->current_password, auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', '¡Contraseña actualizada correctamente!');
    }
}
