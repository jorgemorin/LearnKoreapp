<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador REST para reportes de usuarios.
 *
 * Endpoints:
 *   POST /api/reports              → store()  — Usuario crea un reporte
 *   GET  /api/reports              → index()  — Usuario lista sus propios reportes
 */
class ReportController extends Controller
{
    /**
     * POST /api/reports
     * Usuario autenticado crea un nuevo reporte.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'category'          => ['required', 'string', 'in:' . implode(',', array_keys(UserReport::$categories))],
            'description'       => ['required', 'string', 'max:2000'],
            'related_item_id'   => ['sometimes', 'integer', 'nullable'],
            'related_item_type' => ['sometimes', 'string', 'nullable', 'in:compound,entity'],
        ]);

        $report = UserReport::create([
            'user_id'           => $request->user()->id,
            'category'          => $request->category,
            'description'       => $request->description,
            'related_item_id'   => $request->related_item_id,
            'related_item_type' => $request->related_item_type,
            'status'            => UserReport::STATUS_PENDING,
        ]);

        return response()->json([
            'status' => 'ok',
            'report' => $this->formatReport($report),
        ], 201);
    }

    /**
     * GET /api/reports
     * Lista los propios reportes del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $reports = UserReport::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'data' => $reports->map(fn($r) => $this->formatReport($r)),
            'meta' => [
                'total'        => $reports->total(),
                'current_page' => $reports->currentPage(),
                'last_page'    => $reports->lastPage(),
            ],
        ]);
    }

    private function formatReport(UserReport $r): array
    {
        return [
            'id'                => $r->id,
            'category'          => $r->category,
            'category_label'    => $r->categoryLabel(),
            'description'       => $r->description,
            'status'            => $r->status,
            'status_label'      => $r->statusLabel(),
            'admin_notes'       => $r->admin_notes,
            'related_item_type' => $r->related_item_type,
            'related_item_id'   => $r->related_item_id,
            'created_at'        => $r->created_at->toISOString(),
        ];
    }
}
