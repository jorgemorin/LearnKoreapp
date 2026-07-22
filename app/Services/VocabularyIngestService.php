<?php

namespace App\Services;

use App\Jobs\ParseVocabularyJob;
use App\Models\Compound;
use Illuminate\Support\Facades\Log;

/**
 * Servicio orquestador de la ingesta de vocabulario coreano.
 *
 * Implementa la lógica de caché Hit/Miss:
 *   - Hit:  el compuesto ya existe en el Diccionario Global → se añade directamente
 *           al progreso del usuario sin llamar a la IA.
 *   - Miss: el compuesto no existe → se despacha ParseVocabularyJob para
 *           análisis asíncrono por IA.
 *
 * Este servicio es la única puerta de entrada para añadir vocabulario.
 * No llama a la IA directamente; delega siempre en el Job.
 */
class VocabularyIngestService
{
    /**
     * Ingesta una palabra coreana para un usuario.
     *
     * @param  string $text    Texto en hangul (ej. "학교에서")
     * @param  int    $userId  ID del usuario que añade la palabra
     * @return array  {
     *     'status'   => 'hit' | 'pending',
     *     'compound' => Compound | null,  // Solo en 'hit'
     *     'message'  => string
     * }
     */
    public function ingest(string $text, int $userId): array
    {
        $text = trim($text);

        Log::info('[VocabularyIngest] Petición recibida', [
            'text'    => $text,
            'user_id' => $userId,
        ]);

        // ── CACHE HIT ────────────────────────────────────────────────────────
        // Buscar si el compuesto ya existe en el Diccionario Global
        $existing = Compound::where('full_text', $text)->first();

        if ($existing) {
            // Crear UserProgress si el usuario no lo tiene todavía
            \App\Models\UserProgress::firstOrCreate(
                [
                    'user_id'   => $userId,
                    'item_id'   => $existing->id,
                    'item_type' => 'compound',
                ],
                [
                    'next_review_date' => now()->toDateString(),
                    'ease_factor'      => 2.5,
                    'interval_days'    => 0,
                    'repetitions'      => 0,
                ]
            );

            Log::info('[VocabularyIngest] Cache HIT', ['compound_id' => $existing->id]);

            return [
                'status'   => 'hit',
                'compound' => $existing->load('entities', 'tags'),
                'message'  => 'Palabra encontrada en el diccionario y añadida a tu colección.',
            ];
        }

        // ── CACHE MISS ───────────────────────────────────────────────────────
        // Despachar Job asíncrono para análisis IA
        ParseVocabularyJob::dispatch($text, $userId);

        Log::info('[VocabularyIngest] Cache MISS — Job despachado', [
            'text'    => $text,
            'user_id' => $userId,
        ]);

        return [
            'status'   => 'pending',
            'compound' => null,
            'message'  => 'Palabra enviada a análisis. Estará disponible en tu colección en breve.',
        ];
    }
}
