<?php

namespace App\Jobs;

use App\Contracts\AIParserServiceInterface;
use App\Models\Compound;
use App\Models\Entity;
use App\Models\Tag;
use App\Models\UserProgress;
use App\Support\TagCatalog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job asíncrono: llama a la IA, parsea el resultado y persiste todo en BD.
 *
 * Flujo completo:
 *   1. Invocar AIParserServiceInterface::parse($text)
 *   2. firstOrCreate Entity por (text, type) → garantiza unicidad
 *   3. firstOrCreate Compound por full_text → garantiza idempotencia
 *   4. Crear relaciones compound_entity con position_order
 *   5. firstOrCreate Tags y asociar vía taggables
 *   6. Crear UserProgress inicial para el usuario (si no existe)
 *
 * Reintentos: 3 intentos con backoff exponencial (10s, 30s, 60s).
 * Si los 3 fallan, el Job muere y queda en failed_jobs para inspección.
 */
class ParseVocabularyJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /** Número máximo de reintentos ante fallos de la API IA. */
    public int $tries = 3;

    /** Backoff en segundos entre reintentos: 10s, 30s, 60s. */
    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly string $text,
        public readonly int    $userId,
    ) {}

    public function handle(AIParserServiceInterface $aiParser): void
    {
        Log::info('[ParseVocabularyJob] Iniciando análisis IA', [
            'text'    => $this->text,
            'user_id' => $this->userId,
        ]);

        // 1. Invocar la IA
        $parsed = $aiParser->parse($this->text);

        Log::debug('[ParseVocabularyJob] Respuesta IA recibida', ['parsed' => $parsed]);

        // Todo en una transacción para garantizar atomicidad
        DB::transaction(function () use ($parsed) {
            // 2. Crear/reutilizar entidades morfológicas
            $entityIds = [];
            foreach ($parsed['components'] as $component) {
                $entity = Entity::firstOrCreate(
                    ['text' => $component['text'], 'type' => $component['type']],
                    ['meaning' => $component['meaning'], 'status' => Entity::STATUS_PENDING]
                );
                $entityIds[$component['position_order']] = $entity->id;
            }

            // 3. Crear/reutilizar el Compound
            $fc = $parsed['full_compound'];
            $compound = Compound::firstOrCreate(
                ['full_text' => $fc['text']],
                ['translation' => $fc['translation'], 'status' => Compound::STATUS_PENDING]
            );

            // 4. Sincronizar relaciones compound_entity con position_order
            //    Solo si el compound acaba de ser creado o si le faltan relaciones
            if ($compound->wasRecentlyCreated || $compound->entities()->count() === 0) {
                $syncData = [];
                foreach ($parsed['components'] as $component) {
                    $entityId = $entityIds[$component['position_order']];
                    $syncData[$entityId] = ['position_order' => $component['position_order']];
                }
                $compound->entities()->sync($syncData);
            }

            // 5. Crear/reutilizar Tags y asociar al compound
            //    Solo se persisten tags del catálogo estándar; el resto se ignoran silenciosamente.
            if (! empty($fc['tags'])) {
                $filteredTags = TagCatalog::filterToStandard($fc['tags']);

                if (count($filteredTags) < count($fc['tags'])) {
                    $ignored = array_diff($fc['tags'], $filteredTags);
                    Log::warning('[ParseVocabularyJob] Tags fuera del catálogo ignorados', [
                        'text'    => $this->text,
                        'ignored' => $ignored,
                    ]);
                }

                $tagIds = [];
                foreach ($filteredTags as $tagName) {
                    $tag = Tag::where('name', mb_strtolower(trim($tagName)))->first();
                    if ($tag) {
                        $tagIds[] = $tag->id;
                    }
                }

                if (! empty($tagIds)) {
                    $compound->tags()->syncWithoutDetaching($tagIds);
                }
            }

            // 6. Crear UserProgress inicial para el usuario (si no existe)
            UserProgress::firstOrCreate(
                [
                    'user_id'   => $this->userId,
                    'item_id'   => $compound->id,
                    'item_type' => 'compound',
                ],
                [
                    'next_review_date' => now()->toDateString(),
                    'ease_factor'      => 2.5,
                    'interval_days'    => 0,
                    'repetitions'      => 0,
                ]
            );
        });

        Log::info('[ParseVocabularyJob] Procesado correctamente', [
            'text'    => $this->text,
            'user_id' => $this->userId,
        ]);
    }

    /**
     * Gestión de fallos: loguea el error para facilitar el diagnóstico.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[ParseVocabularyJob] Fallo definitivo tras todos los reintentos', [
            'text'      => $this->text,
            'user_id'   => $this->userId,
            'exception' => $exception->getMessage(),
        ]);
    }
}
