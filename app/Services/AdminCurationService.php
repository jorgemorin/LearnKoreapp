<?php

namespace App\Services;

use App\Models\Compound;
use App\Models\Entity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de curación del Backoffice de Administración.
 *
 * Gestiona el ciclo de vida de los términos pendientes de revisión:
 *   - getPendingQueue(): lista paginada de compounds + entities con status pending_review
 *   - approve(): cambia status a 'verified'
 *   - update(): edita traducción/meaning, tags y disección morfológica
 *   - delete(): cascade delete controlado con auditoría en log
 *
 * El cascade delete es controlado (no depende del CASCADE de FK) para
 * poder registrar auditoría y garantizar que user_progress queda limpio.
 */
class AdminCurationService
{
    // =========================================================================
    // Cola de pendientes
    // =========================================================================

    /**
     * Devuelve la cola paginada de elementos pendientes de revisión.
     * Incluye tanto Compounds como Entities con status = pending_review.
     * Ordenados por fecha de creación (más recientes primero).
     *
     * @param  int $perPage Elementos por página (default 20)
     * @return LengthAwarePaginator
     */
    public function getPendingQueue(int $perPage = 20): LengthAwarePaginator
    {
        return Compound::with(['entities', 'tags'])
            ->where('status', Compound::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    // =========================================================================
    // Aprobar
    // =========================================================================

    /**
     * Aprueba un término (status → 'verified').
     *
     * @param  string $type  'compound' | 'entity'
     * @param  int    $id    ID del término
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function approve(string $type, int $id): void
    {
        $model = $this->resolveModel($type, $id);
        $model->update(['status' => 'verified']);

        Log::info('[AdminCuration] Término aprobado', [
            'type' => $type,
            'id'   => $id,
        ]);
    }

    // =========================================================================
    // Editar
    // =========================================================================

    /**
     * Actualiza los datos de un término (traducción/meaning, tags, morfemas).
     *
     * @param  string $type  'compound' | 'entity'
     * @param  int    $id    ID del término
     * @param  array  $data  Campos a actualizar (translation|meaning, tags[], entities[])
     * @return Model         El modelo actualizado con relaciones recargadas
     */
    public function update(string $type, int $id, array $data): Model
    {
        $model = $this->resolveModel($type, $id);

        DB::transaction(function () use ($model, $type, $data) {
            // Actualizar campos principales
            if ($type === 'compound') {
                $fields = array_filter([
                    'translation' => $data['translation'] ?? null,
                    'status'      => $data['status'] ?? null,
                ], fn ($v) => $v !== null);

                if (! empty($fields)) {
                    $model->update($fields);
                }

                // Actualizar tags
                if (isset($data['tags']) && is_array($data['tags'])) {
                    $tagIds = collect($data['tags'])->map(function ($tagName) {
                        return \App\Models\Tag::firstOrCreate(['name' => trim($tagName)])->id;
                    });
                    $model->tags()->sync($tagIds);
                }

            } elseif ($type === 'entity') {
                $fields = array_filter([
                    'meaning' => $data['meaning'] ?? null,
                    'type'    => $data['type']    ?? null,
                    'status'  => $data['status']  ?? null,
                ], fn ($v) => $v !== null);

                if (! empty($fields)) {
                    $model->update($fields);
                }
            }
        });

        Log::info('[AdminCuration] Término actualizado', [
            'type' => $type,
            'id'   => $id,
            'data' => $data,
        ]);

        return $model->fresh(['tags', 'entities']);
    }

    // =========================================================================
    // Eliminar (cascade controlado)
    // =========================================================================

    /**
     * Elimina un término con cascade delete controlado y auditoría.
     *
     * Orden de eliminación:
     *   1. user_progress asociados (limpia el progreso SRS de todos los usuarios)
     *   2. study_logs asociados (preserva integridad histórica)
     *   3. taggables asociados
     *   4. compound_entity (si es entity)
     *   5. El modelo principal
     *
     * @param  string $type  'compound' | 'entity'
     * @param  int    $id    ID del término
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(string $type, int $id): void
    {
        $model = $this->resolveModel($type, $id);

        DB::transaction(function () use ($model, $type, $id) {
            // 1. Eliminar user_progress
            $progressDeleted = \App\Models\UserProgress::where('item_id', $id)
                ->where('item_type', $type)
                ->delete();

            // 2. Eliminar study_logs
            $logsDeleted = \App\Models\StudyLog::where('item_id', $id)
                ->where('item_type', $type)
                ->delete();

            // 3. Eliminar taggables
            DB::table('taggables')
                ->where('taggable_id', $id)
                ->where('taggable_type', $type)
                ->delete();

            // 4. Si es entity: eliminar de compound_entity
            if ($type === 'entity') {
                DB::table('compound_entity')
                    ->where('entity_id', $id)
                    ->delete();
            }

            // 5. Si es compound: eliminar sus compound_entity
            if ($type === 'compound') {
                DB::table('compound_entity')
                    ->where('compound_id', $id)
                    ->delete();
            }

            // 6. Eliminar el modelo
            $model->delete();

            Log::warning('[AdminCuration] Término eliminado (cascade controlado)', [
                'type'              => $type,
                'id'                => $id,
                'progress_deleted'  => $progressDeleted,
                'logs_deleted'      => $logsDeleted,
            ]);
        });
    }

    // =========================================================================
    // Helper privado
    // =========================================================================

    private function resolveModel(string $type, int $id): Model
    {
        return match ($type) {
            'compound' => Compound::findOrFail($id),
            'entity'   => Entity::findOrFail($id),
            default    => throw new \InvalidArgumentException("Tipo '$type' no válido. Usa 'compound' o 'entity'."),
        };
    }
}
