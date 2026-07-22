<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador REST de Estadísticas Globales (Admin).
 *
 * Endpoint:
 *   GET /api/admin/stats/global → globalStats()
 *
 * Requiere: auth:sanctum + role:admin
 */
class AdminStatsController extends Controller
{
    public function __construct(
        private readonly StatsService $statsService,
    ) {}

    /**
     * GET /api/admin/stats/global
     *
     * Devuelve las estadísticas globales de toda la plataforma:
     *   - Total usuarios, compounds, pendientes
     *   - Total respuestas registradas, sesiones distintas
     *   - Tasa de acierto global de todos los usuarios
     *   - Top 5 tags más estudiados
     */
    public function globalStats(): JsonResponse
    {
        $stats = $this->statsService->getGlobalStats();

        return response()->json([
            'data' => $stats,
            'meta' => ['generated_at' => now()->toDateTimeString()],
        ]);
    }
}
