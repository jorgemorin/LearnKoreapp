<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnswerReviewRequest;
use App\Services\SrsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador REST para la sesión de repaso SRS.
 *
 * Endpoints:
 *   GET  /api/review/next-batch         → nextBatch()  — Lote de tarjetas vencidas
 *   POST /api/review/{progressId}/answer → answer()     — Registrar respuesta SM-2
 */
class ReviewController extends Controller
{
    public function __construct(
        private readonly SrsService $srsService,
    ) {}

    /**
     * GET /api/review/next-batch
     *
     * Devuelve hasta 20 tarjetas con next_review_date <= today para el usuario.
     * Usa el índice crítico (user_id, next_review_date).
     */
    public function nextBatch(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 20), 50); // máx 50
        $batch = $this->srsService->getNextBatch($request->user()->id, $limit);

        return response()->json([
            'data'  => $batch->map(fn ($progress) => $this->formatProgress($progress)),
            'meta'  => [
                'count'      => $batch->count(),
                'date'       => now()->toDateString(),
                'user_id'    => $request->user()->id,
            ],
        ]);
    }

    /**
     * POST /api/review/{progressId}/answer
     *
     * Registra la respuesta del usuario:
     *   1. INSERT en study_logs (inmutable)
     *   2. UPDATE en user_progress con los nuevos valores SM-2
     *
     * Devuelve 403 si el progressId no pertenece al usuario autenticado.
     */
    public function answer(AnswerReviewRequest $request, int $progressId): JsonResponse
    {
        $progress = $this->srsService->recordAnswer(
            progressId:  $progressId,
            userId:      $request->user()->id,
            rating:      $request->resolvedRating(),
            timeTakenMs: (int) $request->validated('time_taken_ms'),
        );

        return response()->json([
            'status'   => 'ok',
            'progress' => [
                'id'               => $progress->id,
                'card_state'       => $progress->card_state,
                'interval_days'    => $progress->interval_days,
                'ease_factor'      => $progress->ease_factor,
                'repetitions'      => $progress->repetitions,
                'lapses'           => $progress->lapses,
                'next_review_date' => $progress->next_review_date->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Helper privado
    // -------------------------------------------------------------------------

    private function formatProgress($progress): array
    {
        $item = $progress->item;

        return [
            'progress_id'      => $progress->id,
            'card_state'       => $progress->card_state,
            'state_label'      => $progress->stateLabel(),
            'next_review_date' => $progress->next_review_date->format('Y-m-d H:i:s'),
            'ease_factor'      => $progress->ease_factor,
            'interval_days'    => $progress->interval_days,
            'repetitions'      => $progress->repetitions,
            'lapses'           => $progress->lapses,
            'item' => $item ? [
                'id'          => $item->id,
                'type'        => $progress->item_type,
                'full_text'   => $item->full_text ?? $item->text,
                'translation' => $item->translation ?? $item->meaning,
            ] : null,
        ];
    }
}
