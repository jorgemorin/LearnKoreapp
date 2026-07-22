<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración Fase B.2: añadir columna `rating` a study_logs.
 *
 * Se añade `rating` (again|hard|good|easy) manteniendo `is_correct`
 * por retrocompatibilidad con los logs históricos y la analítica existente.
 *
 * El campo `rating` es nullable para registros anteriores a esta migración.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_logs', function (Blueprint $table) {
            $table->string('rating', 10)->nullable()
                  ->comment('again | hard | good | easy — calificación Anki de 4 botones')
                  ->after('is_correct');
        });
    }

    public function down(): void
    {
        Schema::table('study_logs', function (Blueprint $table) {
            $table->dropColumn('rating');
        });
    }
};
