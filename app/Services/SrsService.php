<?php

namespace App\Services;

use App\Models\StudyLog;
use App\Models\UserProgress;
use App\Models\UserSrsSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio del Motor SRS — Algoritmo Anki Completo.
 *
 * Implementa el ciclo de vida completo de las tarjetas según la lógica de Anki:
 *
 * ── FASES ────────────────────────────────────────────────────────────────────
 *
 * [NEW] → nueva tarjeta, nunca vista.
 *   Al pulsar cualquier botón distinto a "Again": entra en Learning (paso 0).
 *   Al pulsar "Fácil": salta directamente a Young con easy_interval días.
 *
 * [LEARNING] → pasando por los pasos de aprendizaje inicial (en minutos).
 *   Pasos por defecto: [1m, 10m].
 *   Again:  vuelve al paso 0, ease_factor -= 0.2.
 *   Hard:   repite el paso actual (no avanza).
 *   Good:   avanza al siguiente paso. Si era el último, se gradúa a Young.
 *   Fácil:  gradúa directamente a Young con easy_interval días.
 *
 * [YOUNG] → graduada, intervalo < 21 días.
 *   Again:  → Relearning. lapses++, ease_factor -= 0.2.
 *   Hard:   intervalo × 1.2, ease_factor -= 0.15.
 *   Good:   intervalo × ease_factor.
 *   Fácil:  intervalo × ease_factor × easy_bonus, ease_factor += 0.15.
 *   Si el nuevo intervalo ≥ 21 días → estado Mature.
 *
 * [MATURE] → graduada, intervalo ≥ 21 días. Misma lógica que Young.
 *   Again:  → Relearning. lapses++, ease_factor -= 0.2.
 *
 * [RELEARNING] → falló en Review. Pasos de reaprendizaje (default: [10m]).
 *   Again:  vuelve al paso 0 de relearning.
 *   Hard:   repite el paso actual.
 *   Good:   avanza. Si era el último: vuelve a Young/Mature con intervalo base.
 *   Fácil:  vuelve a Young/Mature directamente.
 *
 * ── CÁLCULO DE INTERVALO EN REVIEW ──────────────────────────────────────────
 *
 * again_interval    = 1 día (reinicia en Relearning)
 * hard_interval     = max(prev_interval + 1, round(prev_interval × 1.2))
 * good_interval     = max(hard_interval + 1, round(prev_interval × ease_factor))
 * easy_interval     = max(good_interval + 1, round(prev_interval × ease_factor × easy_bonus))
 *
 * Todos los intervalos se limitan a max_interval del usuario.
 */
class SrsService
{
    // ── Constantes del algoritmo ──────────────────────────────────────────────
    public const DEFAULT_EASE_FACTOR = 2.5;
    public const MIN_EASE_FACTOR     = 1.3;

    // Ajustes de ease_factor por rating en Review
    private const EF_AGAIN = -0.20;
    private const EF_HARD  = -0.15;
    private const EF_GOOD  =  0.00;
    private const EF_EASY  = +0.15;

    // =========================================================================
    // Cálculo principal: nuevo estado tras un rating
    // =========================================================================

    /**
     * Calcula el nuevo estado Anki completo de una tarjeta tras una calificación.
     *
     * @param  UserProgress    $progress  Estado actual
     * @param  string          $rating    again | hard | good | easy
     * @param  UserSrsSettings $settings  Configuración del usuario
     * @return array {
     *     card_state, interval_days, ease_factor, repetitions,
     *     lapses, learning_step_index, next_review_date,
     *     is_correct
     * }
     */
    public function calculate(
        UserProgress    $progress,
        string          $rating,
        UserSrsSettings $settings,
    ): array {
        $state = $progress->card_state ?? UserProgress::STATE_NEW;

        return match ($state) {
            UserProgress::STATE_NEW        => $this->calculateNew($progress, $rating, $settings),
            UserProgress::STATE_LEARNING   => $this->calculateLearning($progress, $rating, $settings),
            UserProgress::STATE_RELEARNING => $this->calculateRelearning($progress, $rating, $settings),
            default                        => $this->calculateReview($progress, $rating, $settings),
        };
    }

    // ── Fase New ─────────────────────────────────────────────────────────────

    private function calculateNew(UserProgress $p, string $rating, UserSrsSettings $s): array
    {
        if ($rating === StudyLog::RATING_EASY) {
            // Salto directo a Young con easy_interval
            $interval  = min($s->easy_interval, $s->max_interval);
            $cardState = $this->resolveYoungOrMature($interval);
            return $this->buildResult($p, $rating, $cardState, $interval, 1, 0, 0);
        }

        if ($rating === StudyLog::RATING_AGAIN) {
            // Sigue como New hasta la siguiente vez
            return $this->buildResult($p, $rating, UserProgress::STATE_NEW, 0, 0, 0, 0,
                nextReviewMinutes: 1);
        }

        // Hard / Good → entrar en Learning en el primer paso
        $stepIndex = ($rating === StudyLog::RATING_HARD) ? 0 : 0;
        $steps     = $s->getLearningStepsArray();
        $minutes   = $steps[$stepIndex] ?? 1;

        return $this->buildResult($p, $rating, UserProgress::STATE_LEARNING, 0, 0, 0, $stepIndex,
            nextReviewMinutes: $minutes);
    }

    // ── Fase Learning ─────────────────────────────────────────────────────────

    private function calculateLearning(UserProgress $p, string $rating, UserSrsSettings $s): array
    {
        $steps     = $s->getLearningStepsArray();
        $stepIndex = $p->learning_step_index;

        if ($rating === StudyLog::RATING_AGAIN) {
            $ef     = max(self::MIN_EASE_FACTOR, round($p->ease_factor + self::EF_AGAIN, 4));
            return $this->buildResult($p, $rating, UserProgress::STATE_LEARNING, 0, 0, 0, 0,
                nextReviewMinutes: $steps[0] ?? 1, easeFactor: $ef);
        }

        if ($rating === StudyLog::RATING_HARD) {
            // Repite el paso actual
            $minutes = $steps[$stepIndex] ?? 1;
            return $this->buildResult($p, $rating, UserProgress::STATE_LEARNING, 0, 0, 0, $stepIndex,
                nextReviewMinutes: $minutes);
        }

        if ($rating === StudyLog::RATING_EASY) {
            // Gradúa directamente con easy_interval
            $interval  = min($s->easy_interval, $s->max_interval);
            $cardState = $this->resolveYoungOrMature($interval);
            return $this->buildResult($p, $rating, $cardState, $interval, 1, 0, 0);
        }

        // Good: avanza al siguiente paso
        $nextStep = $stepIndex + 1;
        if ($nextStep >= count($steps)) {
            // Último paso completado → gradúa a Young con graduating_interval
            $interval  = min($s->graduating_interval, $s->max_interval);
            $cardState = $this->resolveYoungOrMature($interval);
            return $this->buildResult($p, $rating, $cardState, $interval, 1, 0, 0);
        }

        $minutes = $steps[$nextStep];
        return $this->buildResult($p, $rating, UserProgress::STATE_LEARNING, 0, 0, 0, $nextStep,
            nextReviewMinutes: $minutes);
    }

    // ── Fase Review (Young + Mature) ─────────────────────────────────────────

    private function calculateReview(UserProgress $p, string $rating, UserSrsSettings $s): array
    {
        $efDelta = match($rating) {
            StudyLog::RATING_AGAIN => self::EF_AGAIN,
            StudyLog::RATING_HARD  => self::EF_HARD,
            StudyLog::RATING_EASY  => self::EF_EASY,
            default                => self::EF_GOOD,
        };

        $newEf = max(self::MIN_EASE_FACTOR, round($p->ease_factor + $efDelta, 4));

        if ($rating === StudyLog::RATING_AGAIN) {
            // → Relearning
            $newLapses = $p->lapses + 1;
            $steps     = $s->getRelearningStepsArray();
            return $this->buildResult($p, $rating, UserProgress::STATE_RELEARNING, 1,
                $p->repetitions, $newLapses, 0,
                nextReviewMinutes: $steps[0] ?? 10, easeFactor: $newEf);
        }

        // Calcular intervalos para los 3 ratings restantes
        $prev        = max(1, $p->interval_days);
        $hardInterval = max($prev + 1, (int) round($prev * 1.2));
        $goodInterval = max($hardInterval + 1, (int) round($prev * $p->ease_factor));
        $easyInterval = max($goodInterval + 1, (int) round($prev * $p->ease_factor * $s->easy_bonus));

        $interval = match($rating) {
            StudyLog::RATING_HARD => $hardInterval,
            StudyLog::RATING_EASY => $easyInterval,
            default               => $goodInterval,
        };

        $interval = (int) round($interval * $s->interval_modifier);
        $interval = min(max(1, $interval), $s->max_interval);

        $newReps   = $p->repetitions + 1;
        $cardState = $this->resolveYoungOrMature($interval);

        return $this->buildResult($p, $rating, $cardState, $interval, $newReps, $p->lapses, 0,
            easeFactor: $newEf);
    }

    // ── Fase Relearning ───────────────────────────────────────────────────────

    private function calculateRelearning(UserProgress $p, string $rating, UserSrsSettings $s): array
    {
        $steps     = $s->getRelearningStepsArray();
        $stepIndex = $p->learning_step_index;

        if ($rating === StudyLog::RATING_AGAIN) {
            $ef = max(self::MIN_EASE_FACTOR, round($p->ease_factor + self::EF_AGAIN, 4));
            return $this->buildResult($p, $rating, UserProgress::STATE_RELEARNING, $p->interval_days,
                $p->repetitions, $p->lapses + 1, 0,
                nextReviewMinutes: $steps[0] ?? 10, easeFactor: $ef);
        }

        if ($rating === StudyLog::RATING_HARD) {
            $minutes = $steps[$stepIndex] ?? 10;
            return $this->buildResult($p, $rating, UserProgress::STATE_RELEARNING, $p->interval_days,
                $p->repetitions, $p->lapses, $stepIndex,
                nextReviewMinutes: $minutes);
        }

        if ($rating === StudyLog::RATING_EASY) {
            // Vuelve directamente a Review
            $interval  = max(1, $p->interval_days);
            $cardState = $this->resolveYoungOrMature($interval);
            return $this->buildResult($p, $rating, $cardState, $interval, $p->repetitions + 1, $p->lapses, 0);
        }

        // Good: avanza paso
        $nextStep = $stepIndex + 1;
        if ($nextStep >= count($steps)) {
            // Graduado de relearning → vuelve a Young/Mature
            $interval  = max(1, $p->interval_days);
            $cardState = $this->resolveYoungOrMature($interval);
            return $this->buildResult($p, $rating, $cardState, $interval, $p->repetitions + 1, $p->lapses, 0);
        }

        $minutes = $steps[$nextStep];
        return $this->buildResult($p, $rating, UserProgress::STATE_RELEARNING, $p->interval_days,
            $p->repetitions, $p->lapses, $nextStep,
            nextReviewMinutes: $minutes);
    }

    // ── Helpers internos ─────────────────────────────────────────────────────

    /** Determina si el intervalo corresponde a Young o Mature. */
    private function resolveYoungOrMature(int $interval): string
    {
        return $interval >= UserProgress::MATURE_THRESHOLD_DAYS
            ? UserProgress::STATE_MATURE
            : UserProgress::STATE_YOUNG;
    }

    /**
     * Construye el array de resultado estándar.
     *
     * Si se pasa nextReviewMinutes > 0, la próxima revisión es en minutos (Learning).
     * De lo contrario, se añaden interval_days días al día de hoy.
     */
    private function buildResult(
        UserProgress $p,
        string       $rating,
        string       $cardState,
        int          $intervalDays,
        int          $repetitions,
        int          $lapses,
        int          $stepIndex,
        int          $nextReviewMinutes = 0,
        ?float       $easeFactor = null,
    ): array {
        $ef = $easeFactor ?? $p->ease_factor;

        $nextReviewDate = $nextReviewMinutes > 0
            ? Carbon::now()->addMinutes($nextReviewMinutes)->toDateTimeString()
            : Carbon::today()->addDays(max(0, $intervalDays))->toDateString();

        return [
            'card_state'          => $cardState,
            'interval_days'       => $intervalDays,
            'ease_factor'         => $ef,
            'repetitions'         => $repetitions,
            'lapses'              => $lapses,
            'learning_step_index' => $stepIndex,
            'next_review_date'    => $nextReviewDate,
            'is_correct'          => StudyLog::isCorrectFromRating($rating),
        ];
    }

    // =========================================================================
    // Estimaciones de intervalo para mostrar en la UI (los 4 botones)
    // =========================================================================

    /**
     * Calcula los intervalos estimados para los 4 botones sin modificar la BD.
     * Devuelve etiquetas legibles: "<1min", "10min", "6d", "1mes", etc.
     *
     * @param  UserProgress    $progress
     * @param  UserSrsSettings $settings
     * @return array ['again' => '...', 'hard' => '...', 'good' => '...', 'easy' => '...']
     */
    public function getEstimatedIntervals(UserProgress $progress, UserSrsSettings $settings): array
    {
        $ratings = [StudyLog::RATING_AGAIN, StudyLog::RATING_HARD, StudyLog::RATING_GOOD, StudyLog::RATING_EASY];
        $result  = [];

        foreach ($ratings as $rating) {
            $state = $this->calculate($progress, $rating, $settings);
            $result[$rating] = $this->formatIntervalLabel($state);
        }

        return $result;
    }

    /** Formatea el intervalo calculado en etiqueta legible. */
    private function formatIntervalLabel(array $state): string
    {
        $days = $state['interval_days'];
        $next = $state['next_review_date'];

        // Si la fecha incluye hora, es un intervalo en minutos (Learning)
        if (str_contains($next, ':')) {
            $minutes = (int) Carbon::now()->diffInMinutes(Carbon::parse($next));
            return $minutes < 60 ? "<{$minutes}min" : round($minutes / 60) . 'h';
        }

        if ($days === 0) return '<1d';
        if ($days  < 30) return "{$days}d";
        if ($days  < 365) return round($days / 30) . 'mes';
        return round($days / 365) . 'años';
    }

    // =========================================================================
    // Obtener lote de tarjetas a repasar
    // =========================================================================

    public function getNextBatch(int $userId, int $limit = 20): Collection
    {
        return UserProgress::with(['item'])
            ->forUser($userId)
            ->dueToday()
            ->orderByRaw("CASE card_state
                WHEN 'relearning' THEN 0
                WHEN 'learning'   THEN 1
                WHEN 'young'      THEN 2
                WHEN 'mature'     THEN 3
                WHEN 'new'        THEN 4
                ELSE 5 END")
            ->orderBy('next_review_date')
            ->limit($limit)
            ->get();
    }

    // =========================================================================
    // Registrar respuesta (con transacción)
    // =========================================================================

    /**
     * Registra la calificación de una tarjeta: inserta study_log y actualiza user_progress.
     *
     * @param  int    $progressId   ID de la fila user_progress
     * @param  int    $userId       ID del usuario (validación de propiedad)
     * @param  string $rating       again | hard | good | easy
     * @param  int    $timeTakenMs  Tiempo de respuesta en milisegundos
     * @return UserProgress         Registro actualizado con el nuevo estado Anki
     */
    public function recordAnswer(
        int    $progressId,
        int    $userId,
        string $rating,
        int    $timeTakenMs,
        // Retrocompatibilidad: si se pasa bool se convierte a rating
        ?bool  $isCorrect = null,
    ): UserProgress {
        // Retrocompatibilidad con el sistema anterior (bool)
        if ($isCorrect !== null && ! in_array($rating, [
            StudyLog::RATING_AGAIN, StudyLog::RATING_HARD,
            StudyLog::RATING_GOOD,  StudyLog::RATING_EASY,
        ], true)) {
            $rating = $isCorrect ? StudyLog::RATING_GOOD : StudyLog::RATING_AGAIN;
        }

        $progress = UserProgress::where('id', $progressId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $settings = UserSrsSettings::firstOrCreate(
            ['user_id' => $userId],
            [] // usa los defaults de la migración
        );

        $newState = $this->calculate($progress, $rating, $settings);

        DB::transaction(function () use ($progress, $rating, $timeTakenMs, $newState) {
            StudyLog::create([
                'user_id'       => $progress->user_id,
                'item_id'       => $progress->item_id,
                'item_type'     => $progress->item_type,
                'is_correct'    => $newState['is_correct'],
                'rating'        => $rating,
                'time_taken_ms' => $timeTakenMs,
            ]);

            $progress->update([
                'card_state'          => $newState['card_state'],
                'interval_days'       => $newState['interval_days'],
                'ease_factor'         => $newState['ease_factor'],
                'repetitions'         => $newState['repetitions'],
                'lapses'              => $newState['lapses'],
                'learning_step_index' => $newState['learning_step_index'],
                'next_review_date'    => $newState['next_review_date'],
            ]);

            StatsService::invalidateUserCache($progress->user_id);
            StatsService::invalidateGlobalCache();
        });

        return $progress->fresh();
    }
}
