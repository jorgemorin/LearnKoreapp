<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Migración de las vistas SQL de analítica.
 * Crea tres vistas para el motor de estadísticas:
 *   - v_accuracy_by_entity: tasa de acierto por elemento individual
 *   - v_accuracy_by_tag: tasa de acierto agrupada por etiqueta semántica
 *   - v_accuracy_by_type: tasa de acierto agrupada por tipo morfológico
 *
 * Estas vistas resuelven directamente las métricas del StatsService
 * y se usan en los endpoints GET /api/stats/*.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Vista 1: Tasa de acierto individual por entidad
        DB::statement("
            CREATE VIEW v_accuracy_by_entity AS
            SELECT
                item_id AS entity_id,
                COUNT(*) FILTER (WHERE is_correct)          AS aciertos,
                COUNT(*)                                    AS intentos,
                ROUND(100.0 * COUNT(*) FILTER (WHERE is_correct) / NULLIF(COUNT(*), 0), 2) AS tasa_acierto
            FROM study_logs
            WHERE item_type = 'entity'
            GROUP BY item_id
        ");

        // Vista 2: Rendimiento semántico — acierto agrupado por tag
        DB::statement("
            CREATE VIEW v_accuracy_by_tag AS
            SELECT
                t.id   AS tag_id,
                t.name AS tag_name,
                COUNT(*) FILTER (WHERE sl.is_correct)       AS aciertos,
                COUNT(*)                                    AS intentos,
                ROUND(100.0 * COUNT(*) FILTER (WHERE sl.is_correct) / NULLIF(COUNT(*), 0), 2) AS tasa_acierto
            FROM study_logs sl
            JOIN taggables tg ON tg.taggable_id = sl.item_id AND tg.taggable_type = sl.item_type
            JOIN tags t       ON t.id = tg.tag_id
            GROUP BY t.id, t.name
        ");

        // Vista 3: Rendimiento estructural — acierto agrupado por tipo de entidad
        DB::statement("
            CREATE VIEW v_accuracy_by_type AS
            SELECT
                e.type,
                COUNT(*) FILTER (WHERE sl.is_correct)       AS aciertos,
                COUNT(*)                                    AS intentos,
                ROUND(100.0 * COUNT(*) FILTER (WHERE sl.is_correct) / NULLIF(COUNT(*), 0), 2) AS tasa_acierto
            FROM study_logs sl
            JOIN entities e ON e.id = sl.item_id AND sl.item_type = 'entity'
            GROUP BY e.type
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_accuracy_by_type');
        DB::statement('DROP VIEW IF EXISTS v_accuracy_by_tag');
        DB::statement('DROP VIEW IF EXISTS v_accuracy_by_entity');
    }
};
