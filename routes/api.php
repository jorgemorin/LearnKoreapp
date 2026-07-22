<?php

use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\VocabularyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — LearnKoreapp
|--------------------------------------------------------------------------
| Los endpoints de la API REST están protegidos con auth:sanctum.
| Los endpoints de backoffice añaden además el middleware role:admin.
|
| Fases:
|   Fase 2 — /api/vocabulary, /api/me/collection
|   Fase 3 — /api/review/*
|   Fase 4 — /api/admin/*
|   Fase 5 — /api/stats/*
*/

// Ruta de health check pública
Route::get('/ping', fn () => response()->json(['status' => 'ok']));

// -------------------------------------------------------------------------
// Rutas protegidas por autenticación
// -------------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {

    // Usuario autenticado actual
    Route::get('/user', fn (Request $request) => $request->user());

    // ── Fase 2: Vocabulario ──────────────────────────────────────────────────
    Route::post('/vocabulary',          [VocabularyController::class, 'store'])     ->name('api.vocabulary.store');
    Route::get('/vocabulary/{id}',      [VocabularyController::class, 'show'])      ->name('api.vocabulary.show');
    Route::get('/me/collection',        [VocabularyController::class, 'collection'])->name('api.me.collection');

    // ── Fase 3: Repasos SRS ──────────────────────────────────────────────────
    Route::get('/review/next-batch',            [ReviewController::class, 'nextBatch'])->name('api.review.next-batch');
    Route::post('/review/{progressId}/answer',  [ReviewController::class, 'answer'])   ->name('api.review.answer');

    // ── Fase 5: Estadísticas ────────────────────────────────────────────────────
    Route::get('/stats/personal', [StatsController::class, 'personal'])->name('api.stats.personal');
    Route::get('/stats/cross',    [StatsController::class, 'cross'])   ->name('api.stats.cross');

    // ── Fase 4: Backoffice admin ──────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        // Cola de pendientes (la ruta de prueba de Fase 1 se mantiene funcionando)
        Route::get('/queue',                    [\App\Http\Controllers\Api\Admin\AdminController::class, 'queue'])   ->name('api.admin.queue');
        Route::post('/{type}/{id}/approve',     [\App\Http\Controllers\Api\Admin\AdminController::class, 'approve']) ->name('api.admin.approve');
        Route::put('/{type}/{id}',              [\App\Http\Controllers\Api\Admin\AdminController::class, 'update'])  ->name('api.admin.update');
        Route::delete('/{type}/{id}',           [\App\Http\Controllers\Api\Admin\AdminController::class, 'destroy']) ->name('api.admin.destroy');
        // ── Fase 5: estadísticas globales ───────────────────────────────────────────
        Route::get('/stats/global',             [\App\Http\Controllers\Api\Admin\AdminStatsController::class, 'globalStats'])->name('api.admin.stats.global');
    });
});
