<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de verificación de rol RBAC.
 * Protege rutas que requieren un rol específico ('admin', 'user').
 *
 * Uso en routes: ->middleware('role:admin')
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // El usuario debe estar autenticado
        if (! $request->user()) {
            abort(401, 'No autenticado.');
        }

        // El rol del usuario debe coincidir con el requerido
        if ($request->user()->role !== $role) {
            abort(403, 'Acceso denegado: se requiere el rol "' . $role . '".');
        }

        return $next($request);
    }
}
