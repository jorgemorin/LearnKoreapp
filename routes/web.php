<?php

use App\Http\Controllers\CollectionController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — LearnKoreapp
|--------------------------------------------------------------------------
*/

// ---------------------------------------------------------------------------
// Rutas de autenticación (solo para invitados)
// ---------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/register', Register::class)->name('register');
    Route::get('/login',    Login::class)->name('login');
});

// Logout (método POST por seguridad CSRF)
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->middleware('auth')->name('logout');

// ---------------------------------------------------------------------------
// Rutas protegidas: usuario autenticado
// ---------------------------------------------------------------------------
Route::middleware('auth')->group(function () {

    // Dashboard principal del usuario
    Route::get('/', function () {
        return view('dashboard');
    })->name('dashboard');

    // ── Fase 2: colección de vocabulario ────────────────────────────────────
    Route::get('/collection', [CollectionController::class, 'index'])->name('collection');

    // ── Fase 3: sesión de repaso ─────────────────────────────────────────────
    Route::get('/study', function () {
        return view('study.review');
    })->name('study');

    // ── Fase 5: estadísticas personales ─────────────────────────────────────────────
    Route::get('/stats', function () {
        return view('stats.personal');
    })->name('stats');

    // ── Fase 6: perfil de usuario ─────────────────────────────────────────────
    Route::get('/profile',          [\App\Http\Controllers\ProfileController::class, 'show'])          ->name('profile.show');
    Route::put('/profile',          [\App\Http\Controllers\ProfileController::class, 'update'])        ->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');

    // ────────────────────────────────────────────────────────
    // Rutas de administración — solo admin
    // -----------------------------------------------------------------------
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {

        Route::get('/', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // Fase 4: gestión del backoffice (se implementará)
        // Route::get('/queue', PendingQueue::class)->name('queue');
        // Route::get('/items/{id}/edit', ReviewItem::class)->name('items.edit');

        // Fase 5: estadísticas globales (se implementará)
        // Route::get('/stats', GlobalStats::class)->name('stats');
    });
});
