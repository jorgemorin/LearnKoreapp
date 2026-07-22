<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActionsLog;
use App\Models\Compound;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador REST Admin — Panel de administración completo.
 *
 * Solo accesible con middleware role:admin.
 *
 * Endpoints:
 *   GET    /api/admin/reports                → listReports()
 *   PUT    /api/admin/reports/{id}           → updateReport()
 *   GET    /api/admin/users                  → listUsers()
 *   GET    /api/admin/users/{id}             → showUser()
 *   PUT    /api/admin/users/{id}/role        → updateRole()
 *   PUT    /api/admin/users/{id}/active      → toggleActive()
 *   GET    /api/admin/compounds              → listCompounds()
 *   PUT    /api/admin/compounds/{id}         → updateCompound()
 *   DELETE /api/admin/compounds/{id}         → destroyCompound()
 *   GET    /api/admin/tags                   → listTags()
 *   PUT    /api/admin/tags/{id}              → updateTag()
 *   POST   /api/admin/tags/merge             → mergeTags()
 *   DELETE /api/admin/tags/{id}              → destroyTag()
 *   GET    /api/admin/log                    → auditLog()
 */
class AdminPanelController extends Controller
{
    // =========================================================================
    // Reportes
    // =========================================================================

    public function listReports(Request $request): JsonResponse
    {
        $query = UserReport::with(['user:id,name,email', 'reviewer:id,name'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $reports = $query->paginate(20);

        return response()->json([
            'data' => $reports->map(fn($r) => $this->formatReport($r)),
            'meta' => [
                'total'        => $reports->total(),
                'current_page' => $reports->currentPage(),
                'last_page'    => $reports->lastPage(),
                'open_count'   => UserReport::open()->count(),
            ],
        ]);
    }

    public function updateReport(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status'      => ['required', 'string', \Illuminate\Validation\Rule::in([
                UserReport::STATUS_PENDING,
                UserReport::STATUS_REVIEWING,
                UserReport::STATUS_RESOLVED,
                UserReport::STATUS_DISMISSED,
            ])],
            'admin_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $report = UserReport::findOrFail($id);
        $oldStatus = $report->status;

        $report->update([
            'status'      => $request->status,
            'admin_notes' => $request->admin_notes ?? $report->admin_notes,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        AdminActionsLog::record(
            adminId:    $request->user()->id,
            actionType: 'report.' . $request->status,
            targetType: 'report',
            targetId:   $report->id,
            payload:    ['old_status' => $oldStatus, 'new_status' => $request->status]
        );

        return response()->json([
            'status' => 'ok',
            'report' => $this->formatReport($report->fresh()->load(['user:id,name,email', 'reviewer:id,name'])),
        ]);
    }

    // =========================================================================
    // Gestión de usuarios
    // =========================================================================

    public function listUsers(Request $request): JsonResponse
    {
        $query = User::withCount(['progress', 'studyLogs', 'reports'])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $q = '%' . $request->search . '%';
            $query->where(fn($x) => $x->where('name', 'like', $q)->orWhere('email', 'like', $q));
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('active')) {
            $query->where('is_active', (bool) $request->active);
        }

        $users = $query->paginate(20);

        return response()->json([
            'data' => $users->map(fn($u) => $this->formatUser($u)),
            'meta' => [
                'total'        => $users->total(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    public function showUser(int $id): JsonResponse
    {
        $user = User::withCount(['progress', 'studyLogs', 'reports'])->findOrFail($id);

        // Stats básicas de SRS
        $statsByState = $user->progress()
            ->select('card_state', DB::raw('count(*) as total'))
            ->groupBy('card_state')
            ->pluck('total', 'card_state');

        $accuracy = $user->studyLogs()->count() > 0
            ? round($user->studyLogs()->where('is_correct', true)->count() / $user->studyLogs()->count() * 100)
            : 0;

        return response()->json([
            'user'       => $this->formatUser($user),
            'srs_states' => $statsByState,
            'accuracy'   => $accuracy,
            'reports'    => $user->reports()->orderByDesc('created_at')->take(5)->get()->map(fn($r) => [
                'id'         => $r->id,
                'category'   => $r->categoryLabel(),
                'status'     => $r->statusLabel(),
                'created_at' => $r->created_at->toISOString(),
            ]),
        ]);
    }

    public function updateRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'in:user,admin'],
        ]);

        $user = User::findOrFail($id);

        // No puede quitarse el rol a sí mismo
        if ($user->id === $request->user()->id) {
            return response()->json(['error' => 'No puedes cambiar tu propio rol.'], 403);
        }

        $oldRole = $user->role;
        $user->update(['role' => $request->role]);

        AdminActionsLog::record(
            adminId:    $request->user()->id,
            actionType: 'user.role_change',
            targetType: 'user',
            targetId:   $user->id,
            payload:    ['old_role' => $oldRole, 'new_role' => $request->role]
        );

        return response()->json(['status' => 'ok', 'role' => $user->fresh()->role]);
    }

    public function toggleActive(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['error' => 'No puedes desactivar tu propia cuenta.'], 403);
        }

        $newActive = ! $user->is_active;
        $user->update(['is_active' => $newActive]);

        AdminActionsLog::record(
            adminId:    $request->user()->id,
            actionType: $newActive ? 'user.activate' : 'user.deactivate',
            targetType: 'user',
            targetId:   $user->id,
        );

        return response()->json([
            'status'    => 'ok',
            'is_active' => $newActive,
        ]);
    }

    // =========================================================================
    // Gestión de compounds
    // =========================================================================

    public function listCompounds(Request $request): JsonResponse
    {
        $query = Compound::with(['tags', 'entities'])->orderByDesc('id');

        if ($request->filled('search')) {
            $q = '%' . $request->search . '%';
            $query->where(fn($x) => $x->where('full_text', 'like', $q)->orWhere('translation', 'like', $q));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $compounds = $query->paginate(25);

        return response()->json([
            'data' => $compounds->map(fn($c) => [
                'id'          => $c->id,
                'full_text'   => $c->full_text,
                'translation' => $c->translation,
                'status'      => $c->status,
                'tags'        => $c->tags->pluck('name'),
                'entities'    => $c->entities->map(fn($e) => ['id' => $e->id, 'text' => $e->text, 'type' => $e->type]),
                'users_count' => $c->userProgress()->count(),
            ]),
            'meta' => ['total' => $compounds->total(), 'last_page' => $compounds->lastPage()],
        ]);
    }

    public function updateCompound(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'full_text'   => ['sometimes', 'string', 'max:255'],
            'translation' => ['sometimes', 'string', 'max:255'],
            'status'      => ['sometimes', 'string', 'in:pending,approved,rejected'],
        ]);

        $compound = Compound::findOrFail($id);
        $old      = $compound->only(['full_text', 'translation', 'status']);
        $compound->update($request->only(['full_text', 'translation', 'status']));

        AdminActionsLog::record(
            adminId:    $request->user()->id,
            actionType: 'compound.update',
            targetType: 'compound',
            targetId:   $compound->id,
            payload:    ['before' => $old, 'after' => $compound->fresh()->only(['full_text', 'translation', 'status'])]
        );

        return response()->json(['status' => 'ok', 'compound' => $compound->fresh()]);
    }

    public function destroyCompound(Request $request, int $id): JsonResponse
    {
        $compound = Compound::withCount('userProgress')->findOrFail($id);

        if ($compound->user_progress_count > 0) {
            return response()->json([
                'error'       => 'Este compound tiene progreso de usuarios. Confirma la eliminación.',
                'users_count' => $compound->user_progress_count,
                'requires_force' => true,
            ], 409);
        }

        $compound->delete();

        AdminActionsLog::record(
            adminId:    $request->user()->id,
            actionType: 'compound.delete',
            targetType: 'compound',
            targetId:   $id,
            payload:    ['full_text' => $compound->full_text]
        );

        return response()->json(['status' => 'ok']);
    }

    // =========================================================================
    // Gestión de tags
    // =========================================================================

    public function listTags(Request $request): JsonResponse
    {
        $tags = Tag::withCount(['compounds', 'entities'])
            ->orderBy('layer')
            ->orderBy('name')
            ->get()
            ->map(fn($t) => [
                'id'            => $t->id,
                'name'          => $t->name,
                'layer'         => $t->layer,
                'is_standard'   => $t->is_standard,
                'usage_count'   => $t->compounds_count + $t->entities_count,
            ]);

        return response()->json(['data' => $tags]);
    }

    public function updateTag(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name'  => ['required', 'string', 'max:100', 'unique:tags,name,' . $id],
            'layer' => ['sometimes', 'nullable', 'string', 'in:grammar,register,thematic'],
        ]);

        $tag    = Tag::findOrFail($id);
        $oldName = $tag->name;
        $tag->update($request->only(['name', 'layer', 'description']));

        AdminActionsLog::record(
            adminId:    $request->user()->id,
            actionType: 'tag.rename',
            targetType: 'tag',
            targetId:   $tag->id,
            payload:    ['old_name' => $oldName, 'new_name' => $tag->name]
        );

        return response()->json(['status' => 'ok', 'tag' => $tag->fresh()]);
    }

    public function mergeTags(Request $request): JsonResponse
    {
        $request->validate([
            'source_id' => ['required', 'integer', 'exists:tags,id'],
            'target_id' => ['required', 'integer', 'exists:tags,id', 'different:source_id'],
        ]);

        $source = Tag::findOrFail($request->source_id);
        $target = Tag::findOrFail($request->target_id);

        DB::transaction(function () use ($source, $target) {
            // Obtener todos los taggables del source
            $taggables = DB::table('taggables')
                ->where('tag_id', $source->id)
                ->get(['taggable_id', 'taggable_type']);

            // Insertar en target evitando duplicados
            foreach ($taggables as $taggable) {
                DB::table('taggables')->insertOrIgnore([
                    'tag_id'        => $target->id,
                    'taggable_id'   => $taggable->taggable_id,
                    'taggable_type' => $taggable->taggable_type,
                ]);
            }

            // Eliminar relaciones del source y el tag
            DB::table('taggables')->where('tag_id', $source->id)->delete();
            $source->delete();
        });

        AdminActionsLog::record(
            adminId:    $request->user()->id,
            actionType: 'tag.merge',
            targetType: 'tag',
            targetId:   $target->id,
            payload:    ['merged_from' => $source->name, 'merged_to' => $target->name]
        );

        return response()->json(['status' => 'ok', 'target_tag' => $target->fresh()]);
    }

    public function destroyTag(Request $request, int $id): JsonResponse
    {
        $tag = Tag::withCount(['compounds', 'entities'])->findOrFail($id);

        if (($tag->compounds_count + $tag->entities_count) > 0) {
            return response()->json([
                'error'       => 'Este tag tiene ' . ($tag->compounds_count + $tag->entities_count) . ' usos. Usa merge o reasigna antes de eliminar.',
                'usage_count' => $tag->compounds_count + $tag->entities_count,
            ], 409);
        }

        $tag->delete();

        AdminActionsLog::record(
            adminId:    $request->user()->id,
            actionType: 'tag.delete',
            targetType: 'tag',
            targetId:   $id,
            payload:    ['name' => $tag->name]
        );

        return response()->json(['status' => 'ok']);
    }

    // =========================================================================
    // Log de auditoría
    // =========================================================================

    public function auditLog(Request $request): JsonResponse
    {
        $query = AdminActionsLog::with('admin:id,name,email')
            ->orderByDesc('created_at');

        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }
        if ($request->filled('action_type')) {
            $query->where('action_type', 'like', $request->action_type . '%');
        }

        $logs = $query->paginate(30);

        return response()->json([
            'data' => $logs->map(fn($l) => [
                'id'          => $l->id,
                'admin'       => $l->admin ? ['id' => $l->admin->id, 'name' => $l->admin->name] : null,
                'action_type' => $l->action_type,
                'target_type' => $l->target_type,
                'target_id'   => $l->target_id,
                'payload'     => $l->payload,
                'created_at'  => $l->created_at->toISOString(),
            ]),
            'meta' => ['total' => $logs->total(), 'last_page' => $logs->lastPage()],
        ]);
    }

    // =========================================================================
    // Helpers privados
    // =========================================================================

    private function formatReport(UserReport $r): array
    {
        return [
            'id'                => $r->id,
            'category'          => $r->category,
            'category_label'    => $r->categoryLabel(),
            'description'       => $r->description,
            'status'            => $r->status,
            'status_label'      => $r->statusLabel(),
            'status_color'      => $r->statusColor(),
            'admin_notes'       => $r->admin_notes,
            'related_item_type' => $r->related_item_type,
            'related_item_id'   => $r->related_item_id,
            'user'              => $r->user ? ['id' => $r->user->id, 'name' => $r->user->name, 'email' => $r->user->email] : null,
            'reviewer'          => $r->reviewer ? ['id' => $r->reviewer->id, 'name' => $r->reviewer->name] : null,
            'reviewed_at'       => $r->reviewed_at?->toISOString(),
            'created_at'        => $r->created_at->toISOString(),
        ];
    }

    private function formatUser(User $u): array
    {
        return [
            'id'             => $u->id,
            'name'           => $u->name,
            'email'          => $u->email,
            'role'           => $u->role,
            'is_active'      => $u->is_active,
            'progress_count' => $u->progress_count ?? null,
            'logs_count'     => $u->study_logs_count ?? null,
            'reports_count'  => $u->reports_count ?? null,
            'created_at'     => $u->created_at->toISOString(),
        ];
    }
}
