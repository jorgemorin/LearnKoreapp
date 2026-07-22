<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVocabularyRequest;
use App\Models\Compound;
use App\Models\UserProgress;
use App\Services\VocabularyIngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador REST para gestión de vocabulario.
 *
 * Endpoints:
 *   POST   /api/vocabulary              → store()   — Ingestar nueva palabra
 *   GET    /api/vocabulary/{id}         → show()    — Detalle de un compound
 *   GET    /api/me/collection           → collection() — Colección personal del usuario
 */
class VocabularyController extends Controller
{
    public function __construct(
        private readonly VocabularyIngestService $ingestService,
    ) {}

    /**
     * POST /api/vocabulary
     *
     * Ingestar una nueva palabra coreana.
     * Devuelve 200 (hit) o 202 (pending/miss) según el estado.
     */
    public function store(StoreVocabularyRequest $request): JsonResponse
    {
        $result = $this->ingestService->ingest(
            text:   $request->validated('text'),
            userId: $request->user()->id,
        );

        $statusCode = $result['status'] === 'hit' ? 200 : 202;

        return response()->json([
            'status'  => $result['status'],
            'message' => $result['message'],
            'data'    => $result['compound'] ? $this->formatCompound($result['compound']) : null,
        ], $statusCode);
    }

    /**
     * GET /api/vocabulary/{id}
     *
     * Detalle de un compound con sus entidades y etiquetas.
     */
    public function show(int $id): JsonResponse
    {
        $compound = Compound::with(['entities', 'tags'])->findOrFail($id);

        return response()->json([
            'data' => $this->formatCompound($compound),
        ]);
    }

    /**
     * GET /api/me/collection
     *
     * Colección personal del usuario autenticado con estado SRS.
     * Ordenada por próxima fecha de repaso (más urgente primero).
     */
    public function collection(Request $request): JsonResponse
    {
        $progress = UserProgress::with(['item'])
            ->forUser($request->user()->id)
            ->where('item_type', 'compound')
            ->orderBy('next_review_date')
            ->paginate(20);

        return response()->json([
            'data'  => $progress->map(fn ($p) => [
                'progress_id'      => $p->id,
                'next_review_date' => $p->next_review_date->toDateString(),
                'ease_factor'      => $p->ease_factor,
                'interval_days'    => $p->interval_days,
                'repetitions'      => $p->repetitions,
                'compound'         => $p->item ? $this->formatCompound($p->item) : null,
            ]),
            'meta'  => [
                'total'        => $progress->total(),
                'per_page'     => $progress->perPage(),
                'current_page' => $progress->currentPage(),
                'last_page'    => $progress->lastPage(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    private function formatCompound(Compound $compound): array
    {
        return [
            'id'          => $compound->id,
            'full_text'   => $compound->full_text,
            'translation' => $compound->translation,
            'status'      => $compound->status,
            'tags'        => $compound->tags?->pluck('name') ?? [],
            'entities'    => $compound->entities?->map(fn ($e) => [
                'id'             => $e->id,
                'text'           => $e->text,
                'type'           => $e->type,
                'meaning'        => $e->meaning,
                'position_order' => $e->pivot?->position_order,
            ]) ?? [],
        ];
    }
}
