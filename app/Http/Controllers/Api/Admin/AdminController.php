<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTermRequest;
use App\Services\AdminCurationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador REST del Backoffice de Administración.
 *
 * Todos los endpoints requieren: auth:sanctum + role:admin
 *
 * Endpoints:
 *   GET    /api/admin/queue              → queue()    — Cola de pendientes
 *   POST   /api/admin/{type}/{id}/approve → approve() — Aprobar término
 *   PUT    /api/admin/{type}/{id}         → update()  — Editar término
 *   DELETE /api/admin/{type}/{id}         → destroy() — Eliminar con cascade
 */
class AdminController extends Controller
{
    public function __construct(
        private readonly AdminCurationService $curationService,
    ) {}

    /**
     * GET /api/admin/queue?page=1&per_page=20
     *
     * Lista paginada de compounds pendientes de revisión.
     */
    public function queue(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('per_page', 20), 100);
        $queue   = $this->curationService->getPendingQueue($perPage);

        return response()->json([
            'data' => $queue->map(fn ($compound) => [
                'id'          => $compound->id,
                'type'        => 'compound',
                'full_text'   => $compound->full_text,
                'translation' => $compound->translation,
                'status'      => $compound->status,
                'created_at'  => $compound->created_at->toDateTimeString(),
                'tags'        => $compound->tags->pluck('name'),
                'entities'    => $compound->entities->map(fn ($e) => [
                    'id'             => $e->id,
                    'text'           => $e->text,
                    'type'           => $e->type,
                    'meaning'        => $e->meaning,
                    'position_order' => $e->pivot?->position_order,
                ]),
            ]),
            'meta' => [
                'total'        => $queue->total(),
                'per_page'     => $queue->perPage(),
                'current_page' => $queue->currentPage(),
                'last_page'    => $queue->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/admin/{type}/{id}/approve
     *
     * Aprueba un término (status → 'verified').
     */
    public function approve(string $type, int $id): JsonResponse
    {
        $this->curationService->approve($type, $id);

        return response()->json([
            'status'  => 'ok',
            'message' => "Término {$type} #{$id} aprobado correctamente.",
        ]);
    }

    /**
     * PUT /api/admin/{type}/{id}
     *
     * Actualiza traducción/meaning, tags y estado de un término.
     */
    public function update(UpdateTermRequest $request, string $type, int $id): JsonResponse
    {
        $model = $this->curationService->update($type, $id, $request->validated());

        return response()->json([
            'status' => 'ok',
            'data'   => $model,
        ]);
    }

    /**
     * DELETE /api/admin/{type}/{id}
     *
     * Elimina un término con cascade delete controlado.
     * También limpia user_progress de todos los usuarios.
     */
    public function destroy(string $type, int $id): JsonResponse
    {
        $this->curationService->delete($type, $id);

        return response()->json([
            'status'  => 'ok',
            'message' => "Término {$type} #{$id} eliminado correctamente con todos sus registros asociados.",
        ], 200);
    }
}
