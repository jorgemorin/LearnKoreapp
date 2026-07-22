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

    // ── Fase C: Gestión de la colección ─────────────────────────────────────
    Route::prefix('collection')->name('api.collection.')->group(function () {
        Route::post('/batch',          [\App\Http\Controllers\Api\CollectionController::class, 'batch'])             ->name('batch');
        Route::put('/{id}/translate',  [\App\Http\Controllers\Api\CollectionController::class, 'updateTranslation']) ->name('translate');
        Route::put('/{id}/suspend',    [\App\Http\Controllers\Api\CollectionController::class, 'toggleSuspend'])     ->name('suspend');
        Route::put('/{id}/interval',   [\App\Http\Controllers\Api\CollectionController::class, 'adjustInterval'])    ->name('interval');
        Route::delete('/{id}',         [\App\Http\Controllers\Api\CollectionController::class, 'destroy'])           ->name('destroy');
    });

    // ── Fase 3: Repasos SRS ──────────────────────────────────────────────────
    Route::get('/review/next-batch',            [ReviewController::class, 'nextBatch'])->name('api.review.next-batch');
    Route::post('/review/{progressId}/answer',  [ReviewController::class, 'answer'])   ->name('api.review.answer');

    // ── Fase 5: Estadísticas ────────────────────────────────────────────────────
    Route::get('/stats/personal', [StatsController::class, 'personal'])->name('api.stats.personal');
    Route::get('/stats/cross',    [StatsController::class, 'cross'])   ->name('api.stats.cross');

    // ── Fase D: Reportes de usuarios ─────────────────────────────────────────
    Route::prefix('reports')->name('api.reports.')->group(function () {
        Route::get('/',  [\App\Http\Controllers\Api\ReportController::class, 'index']) ->name('index');
        Route::post('/', [\App\Http\Controllers\Api\ReportController::class, 'store']) ->name('store');
    });

    // ── Fase 4: Backoffice admin ──────────────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->name('api.admin.')->group(function () {
        // Existentes (Fases 1–2)
        Route::get('/queue',               [\App\Http\Controllers\Api\Admin\AdminController::class, 'queue'])   ->name('queue');
        Route::post('/{type}/{id}/approve',[\App\Http\Controllers\Api\Admin\AdminController::class, 'approve']) ->name('approve')->where('type', 'compound|entity');
        Route::put('/{type}/{id}',         [\App\Http\Controllers\Api\Admin\AdminController::class, 'update'])  ->name('update')->where('type', 'compound|entity');
        Route::delete('/{type}/{id}',      [\App\Http\Controllers\Api\Admin\AdminController::class, 'destroy']) ->name('destroy')->where('type', 'compound|entity');
        Route::get('/stats/global',        [\App\Http\Controllers\Api\Admin\AdminStatsController::class, 'globalStats'])->name('stats.global');

        // Fase D — Panel completo
        Route::get('/reports',             [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'listReports'])    ->name('reports.index');
        Route::put('/reports/{id}',        [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'updateReport'])   ->name('reports.update');
        Route::get('/users',               [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'listUsers'])      ->name('users.index');
        Route::get('/users/{id}',          [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'showUser'])       ->name('users.show');
        Route::put('/users/{id}/role',     [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'updateRole'])     ->name('users.role');
        Route::put('/users/{id}/active',   [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'toggleActive'])   ->name('users.active');
        Route::get('/compounds',           [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'listCompounds'])  ->name('compounds.index');
        Route::put('/compounds/{id}',      [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'updateCompound']) ->name('compounds.update');
        Route::delete('/compounds/{id}',   [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'destroyCompound'])->name('compounds.destroy');
        Route::get('/tags',                [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'listTags'])       ->name('tags.index');
        Route::put('/tags/{id}',           [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'updateTag'])      ->name('tags.update');
        Route::post('/tags/merge',         [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'mergeTags'])      ->name('tags.merge');
        Route::delete('/tags/{id}',        [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'destroyTag'])     ->name('tags.destroy');
        Route::get('/log',                 [\App\Http\Controllers\Api\Admin\AdminPanelController::class, 'auditLog'])       ->name('log');
    });
});
