<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compound;
use App\Models\StudyLog;
use App\Models\UserProgress;
use App\Models\UserSrsSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Controlador REST para la colección personal del usuario.
 *
 * Endpoints:
 *   PUT    /api/collection/{id}/translate   — edición inline de traducción
 *   PUT    /api/collection/{id}/suspend     — suspender / reactivar tarjeta
 *   PUT    /api/collection/{id}/interval    — ajuste manual de intervalo
 *   DELETE /api/collection/{id}             — eliminar de colección (solo progress)
 *   POST   /api/collection/batch            — acciones en lote
 */
class CollectionController extends Controller
{
    /**
     * PUT /api/collection/{id}/translate
     * Edita la traducción de un compound de la colección del usuario.
     * Solo compounds que el usuario tiene en su colección.
     */
    public function updateTranslation(Request $request, int $progressId): JsonResponse
    {
        $request->validate([
            'translation' => ['required', 'string', 'max:255'],
        ]);

        $progress = UserProgress::where('id', $progressId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $progress->item->update(['translation' => $request->translation]);

        return response()->json([
            'status'      => 'ok',
            'translation' => $progress->item->fresh()->translation,
        ]);
    }

    /**
     * PUT /api/collection/{id}/suspend
     * Alterna el estado suspended de una tarjeta.
     */
    public function toggleSuspend(Request $request, int $progressId): JsonResponse
    {
        $progress = UserProgress::where('id', $progressId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $newState = $progress->card_state === UserProgress::STATE_SUSPENDED
            ? UserProgress::STATE_NEW
            : UserProgress::STATE_SUSPENDED;

        $progress->update(['card_state' => $newState]);

        return response()->json([
            'status'     => 'ok',
            'card_state' => $newState,
            'suspended'  => $newState === UserProgress::STATE_SUSPENDED,
        ]);
    }

    /**
     * PUT /api/collection/{id}/interval
     * Ajuste manual del intervalo de repaso (en días).
     * También permite resetear la tarjeta a Learning.
     */
    public function adjustInterval(Request $request, int $progressId): JsonResponse
    {
        $request->validate([
            'interval_days' => ['required', 'integer', 'min:0', 'max:36500'],
            'reset'         => ['sometimes', 'boolean'],
        ]);

        $progress = UserProgress::where('id', $progressId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $settings = UserSrsSettings::firstOrCreate(['user_id' => $request->user()->id]);

        if ($request->boolean('reset')) {
            // Resetear a Learning
            $steps = $settings->getLearningStepsArray();
            $progress->update([
                'card_state'          => UserProgress::STATE_LEARNING,
                'interval_days'       => 0,
                'repetitions'         => 0,
                'lapses'              => 0,
                'learning_step_index' => 0,
                'ease_factor'         => 2.5,
                'next_review_date'    => now()->addMinutes($steps[0] ?? 1)->toDateTimeString(),
            ]);
        } else {
            $days = (int) $request->interval_days;
            $cardState = $days >= UserProgress::MATURE_THRESHOLD_DAYS
                ? UserProgress::STATE_MATURE
                : ($days > 0 ? UserProgress::STATE_YOUNG : UserProgress::STATE_LEARNING);

            $progress->update([
                'interval_days'    => $days,
                'card_state'       => $cardState,
                'next_review_date' => now()->addDays($days)->toDateString(),
            ]);
        }

        // Registrar en study_log como ajuste manual
        StudyLog::create([
            'user_id'       => $request->user()->id,
            'item_id'       => $progress->item_id,
            'item_type'     => $progress->item_type,
            'is_correct'    => true,
            'rating'        => 'good',
            'time_taken_ms' => 0,
        ]);

        return response()->json([
            'status'           => 'ok',
            'card_state'       => $progress->fresh()->card_state,
            'interval_days'    => $progress->fresh()->interval_days,
            'next_review_date' => $progress->fresh()->next_review_date,
        ]);
    }

    /**
     * DELETE /api/collection/{id}
     * Elimina la tarjeta de la colección del usuario (solo user_progress, no el compound).
     */
    public function destroy(Request $request, int $progressId): JsonResponse
    {
        $progress = UserProgress::where('id', $progressId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $progress->delete();

        return response()->json(['status' => 'ok']);
    }

    /**
     * POST /api/collection/batch
     * Acciones en lote sobre múltiples tarjetas.
     * Body: { "ids": [1,2,3], "action": "suspend"|"reset"|"delete" }
     */
    public function batch(Request $request): JsonResponse
    {
        $request->validate([
            'ids'    => ['required', 'array', 'min:1', 'max:200'],
            'ids.*'  => ['integer'],
            'action' => ['required', 'string', 'in:suspend,unsuspend,reset,delete'],
        ]);

        $userId = $request->user()->id;
        $ids    = $request->input('ids');
        $action = $request->input('action');

        // Asegurar que todos los IDs pertenecen al usuario
        $ownedIds = UserProgress::where('user_id', $userId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->toArray();

        $count = 0;

        DB::transaction(function () use ($ownedIds, $action, $userId, &$count) {
            switch ($action) {
                case 'suspend':
                    $count = UserProgress::whereIn('id', $ownedIds)
                        ->update(['card_state' => UserProgress::STATE_SUSPENDED]);
                    break;

                case 'unsuspend':
                    $count = UserProgress::whereIn('id', $ownedIds)
                        ->update(['card_state' => UserProgress::STATE_NEW]);
                    break;

                case 'reset':
                    $settings = UserSrsSettings::firstOrCreate(['user_id' => $userId]);
                    $steps    = $settings->getLearningStepsArray();
                    $count    = UserProgress::whereIn('id', $ownedIds)
                        ->update([
                            'card_state'          => UserProgress::STATE_LEARNING,
                            'interval_days'       => 0,
                            'repetitions'         => 0,
                            'lapses'              => 0,
                            'learning_step_index' => 0,
                            'ease_factor'         => 2.5,
                            'next_review_date'    => now()->addMinutes($steps[0] ?? 1)->toDateTimeString(),
                        ]);
                    break;

                case 'delete':
                    $count = UserProgress::whereIn('id', $ownedIds)->delete();
                    break;
            }
        });

        return response()->json([
            'status'   => 'ok',
            'action'   => $action,
            'affected' => $count,
        ]);
    }
}
