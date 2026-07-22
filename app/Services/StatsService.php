<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de Analítica — Motor de Estadísticas.
 *
 * Implementa tres capas de análisis:
 *   1. Estadísticas personales: tasa de acierto global, por tag, por tipo morfológico
 *   2. Análisis cruzado: tabla cruzada tipo × tag
 *   3. Estadísticas globales: métricas de toda la plataforma (para admin)
 *
 * Las consultas pesadas se cachean con TTL de 5 minutos usando el store configurado.
 * La caché se invalida automáticamente al registrar una nueva respuesta (recordAnswer en SrsService).
 *
 * Compatibilidad: usa SUM(CASE...) en vez de COUNT(*) FILTER (WHERE...) para funcionar
 * tanto en PostgreSQL como en SQLite (tests).
 */
class StatsService
{
    /** TTL de caché en segundos (5 minutos). */
    private const CACHE_TTL = 300;

    // =========================================================================
    // Estadísticas Personales
    // =========================================================================

    /**
     * Calcula las estadísticas de rendimiento personal de un usuario.
     *
     * @param  int $userId
     * @return array {
     *     global_accuracy: float|null,
     *     total_studied: int,
     *     due_today: int,
     *     total_in_collection: int,
     *     accuracy_by_tag: array[{tag_name, accuracy, attempts, aciertos}],
     *     accuracy_by_type: array[{type, accuracy, attempts, aciertos}],
     *     recent_sessions: array[{date, total, aciertos, accuracy}]
     * }
     */
    public function getPersonalStats(int $userId): array
    {
        $cacheKey = "stats.personal.{$userId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId) {
            return [
                'global_accuracy'      => $this->globalAccuracy($userId),
                'total_studied'        => $this->totalStudied($userId),
                'due_today'            => $this->dueToday($userId),
                'total_in_collection'  => $this->totalInCollection($userId),
                'accuracy_by_tag'      => $this->accuracyByTag($userId),
                'accuracy_by_type'     => $this->accuracyByType($userId),
                'recent_sessions'      => $this->recentSessions($userId, days: 7),
            ];
        });
    }

    // =========================================================================
    // Análisis Cruzado — Tipo × Tag
    // =========================================================================

    /**
     * Genera la tabla cruzada de precisión: tipo morfológico × etiqueta semántica.
     * Filtrable opcionalmente por tipo y/o tag.
     *
     * @param  int         $userId
     * @param  string|null $type  'root' | 'particle' | 'word'
     * @param  string|null $tag   nombre de tag (parcial, case-insensitive)
     * @return array
     */
    public function getCrossAnalysis(int $userId, ?string $type = null, ?string $tag = null): array
    {
        $cacheKey = "stats.cross.{$userId}." . md5("{$type}|{$tag}");

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $type, $tag) {
            $query = DB::table('study_logs as sl')
                ->join('user_progress as up', function ($join) use ($userId) {
                    $join->on('up.item_id', '=', 'sl.item_id')
                         ->on('up.item_type', '=', 'sl.item_type')
                         ->where('up.user_id', '=', $userId);
                })
                ->join('taggables as tg', function ($join) {
                    $join->on('tg.taggable_id', '=', 'sl.item_id')
                         ->on('tg.taggable_type', '=', 'sl.item_type');
                })
                ->join('tags as t', 't.id', '=', 'tg.tag_id')
                ->leftJoin('compound_entity as ce', function ($join) {
                    $join->on('ce.compound_id', '=', 'sl.item_id')
                         ->where('sl.item_type', '=', 'compound');
                })
                ->leftJoin('entities as e', 'e.id', '=', 'ce.entity_id')
                ->where('sl.user_id', $userId)
                ->select(
                    't.name as tag_name',
                    DB::raw('COALESCE(e.type, sl.item_type) as item_type'),
                    DB::raw('COUNT(*) as intentos'),
                    DB::raw('SUM(CASE WHEN sl.is_correct THEN 1 ELSE 0 END) as aciertos'),
                    DB::raw('ROUND(100.0 * SUM(CASE WHEN sl.is_correct THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 1) as tasa_acierto')
                )
                ->groupBy('t.name', DB::raw('COALESCE(e.type, sl.item_type)'));

            if ($type) {
                $query->where(DB::raw('COALESCE(e.type, sl.item_type)'), $type);
            }
            if ($tag) {
                $query->where('t.name', 'like', "%{$tag}%");
            }

            return $query->orderBy('tag_name')->get()->toArray();
        });
    }

    // =========================================================================
    // Estadísticas Globales (Admin)
    // =========================================================================

    /**
     * Estadísticas globales de toda la plataforma.
     * Solo para usuarios admin.
     *
     * @return array
     */
    public function getGlobalStats(): array
    {
        return Cache::remember('stats.global', self::CACHE_TTL, function () {
            $totalUsers    = DB::table('users')->count();
            $totalCompounds = DB::table('compounds')->count();
            $pendingReview = DB::table('compounds')->where('status', 'pending_review')->count();
            $totalStudied  = DB::table('study_logs')->count();
            $totalSessions = DB::table('study_logs')
                ->select(DB::raw('DATE(created_at) as date'), 'user_id')
                ->distinct()
                ->get()
                ->count();

            $globalAccuracy = DB::table('study_logs')
                ->selectRaw('ROUND(100.0 * SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 1) as accuracy')
                ->value('accuracy');

            $topTags = DB::table('study_logs as sl')
                ->join('taggables as tg', function ($join) {
                    $join->on('tg.taggable_id', '=', 'sl.item_id')
                         ->on('tg.taggable_type', '=', 'sl.item_type');
                })
                ->join('tags as t', 't.id', '=', 'tg.tag_id')
                ->select('t.name as tag', DB::raw('COUNT(*) as intentos'))
                ->groupBy('t.name')
                ->orderByDesc('intentos')
                ->limit(5)
                ->get();

            return [
                'total_users'      => $totalUsers,
                'total_compounds'  => $totalCompounds,
                'pending_review'   => $pendingReview,
                'total_studied'    => $totalStudied,
                'total_sessions'   => $totalSessions,
                'global_accuracy'  => $globalAccuracy,
                'top_tags'         => $topTags,
            ];
        });
    }

    // =========================================================================
    // Invalidación de caché
    // =========================================================================

    /**
     * Invalida la caché de estadísticas personales del usuario.
     * Se llama desde SrsService::recordAnswer().
     */
    public static function invalidateUserCache(int $userId): void
    {
        Cache::forget("stats.personal.{$userId}");
        // La caché de cross-analysis tiene claves con md5, se deja expirar por TTL
    }

    /**
     * Invalida la caché global (admin).
     */
    public static function invalidateGlobalCache(): void
    {
        Cache::forget('stats.global');
    }

    // =========================================================================
    // Queries internas — compatibles con SQLite y PostgreSQL
    // =========================================================================

    private function globalAccuracy(int $userId): ?float
    {
        $result = DB::table('study_logs')
            ->where('user_id', $userId)
            ->selectRaw('ROUND(100.0 * SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 1) as accuracy')
            ->value('accuracy');

        return $result !== null ? (float) $result : null;
    }

    private function totalStudied(int $userId): int
    {
        return (int) DB::table('study_logs')->where('user_id', $userId)->count();
    }

    private function dueToday(int $userId): int
    {
        return (int) DB::table('user_progress')
            ->where('user_id', $userId)
            ->whereDate('next_review_date', '<=', now()->toDateString())
            ->count();
    }

    private function totalInCollection(int $userId): int
    {
        return (int) DB::table('user_progress')->where('user_id', $userId)->count();
    }

    private function accuracyByTag(int $userId): array
    {
        return DB::table('study_logs as sl')
            ->where('sl.user_id', $userId)
            ->join('taggables as tg', function ($join) {
                $join->on('tg.taggable_id', '=', 'sl.item_id')
                     ->on('tg.taggable_type', '=', 'sl.item_type');
            })
            ->join('tags as t', 't.id', '=', 'tg.tag_id')
            ->select(
                't.name as tag_name',
                DB::raw('COUNT(*) as intentos'),
                DB::raw('SUM(CASE WHEN sl.is_correct THEN 1 ELSE 0 END) as aciertos'),
                DB::raw('ROUND(100.0 * SUM(CASE WHEN sl.is_correct THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 1) as tasa_acierto')
            )
            ->groupBy('t.id', 't.name')
            ->orderByDesc('intentos')
            ->get()
            ->toArray();
    }

    private function accuracyByType(int $userId): array
    {
        return DB::table('study_logs as sl')
            ->where('sl.user_id', $userId)
            ->where('sl.item_type', 'entity')
            ->join('entities as e', 'e.id', '=', 'sl.item_id')
            ->select(
                'e.type',
                DB::raw('COUNT(*) as intentos'),
                DB::raw('SUM(CASE WHEN sl.is_correct THEN 1 ELSE 0 END) as aciertos'),
                DB::raw('ROUND(100.0 * SUM(CASE WHEN sl.is_correct THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 1) as tasa_acierto')
            )
            ->groupBy('e.type')
            ->orderByDesc('intentos')
            ->get()
            ->toArray();
    }

    private function recentSessions(int $userId, int $days = 7): array
    {
        return DB::table('study_logs')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw("DATE(created_at) as date"),
                DB::raw('COUNT(*) as intentos'),
                DB::raw('SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) as aciertos'),
                DB::raw('ROUND(100.0 * SUM(CASE WHEN is_correct THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 1) as tasa_acierto')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
