<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador REST de Estadísticas Personales.
 *
 * Endpoints:
 *   GET /api/stats/personal          → personal()  — Dashboard personal del usuario
 *   GET /api/stats/cross?type=&tag=  → cross()     — Análisis cruzado tipo × tag
 */
class StatsController extends Controller
{
    public function __construct(
        private readonly StatsService $statsService,
    ) {}

    /**
     * GET /api/stats/personal
     *
     * Devuelve las estadísticas completas del usuario autenticado:
     *   - Tasa de acierto global
     *   - Total estudiadas / pendientes hoy / en colección
     *   - Acierto por tag semántico
     *   - Acierto por tipo morfológico
     *   - Sesiones recientes (últimos 7 días)
     */
    public function personal(Request $request): JsonResponse
    {
        $stats = $this->statsService->getPersonalStats($request->user()->id);

        return response()->json([
            'data' => $stats,
            'meta' => [
                'user_id'    => $request->user()->id,
                'generated_at' => now()->toDateTimeString(),
                'cache_ttl'  => 300,
            ],
        ]);
    }

    /**
     * GET /api/stats/cross?type=root&tag=Educación
     *
     * Análisis cruzado: combinación tipo morfológico × etiqueta semántica.
     * Ambos parámetros son opcionales (filtros).
     */
    public function cross(Request $request): JsonResponse
    {
        $type   = $request->get('type');
        $tag    = $request->get('tag');

        $matrix = $this->statsService->getCrossAnalysis(
            userId: $request->user()->id,
            type:   $type,
            tag:    $tag,
        );

        return response()->json([
            'data'    => $matrix,
            'filters' => compact('type', 'tag'),
        ]);
    }
}
