<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de la tabla user_progress (Sistema SRS).
 * Registra el estado de revisión de cada elemento (entity o compound)
 * para cada usuario. La combinación (user_id, item_id, item_type) es única.
 *
 * Campos clave del algoritmo SM-2:
 *   - ease_factor: factor de facilidad (2.5 por defecto)
 *   - interval_days: días hasta la próxima revisión
 *   - repetitions: número de repeticiones consecutivas correctas
 *   - next_review_date: fecha objetivo de la próxima revisión
 *
 * El índice crítico (user_id, next_review_date) cubre el RNF de <200ms
 * para la consulta de "siguiente lote de tarjetas a repasar".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_type', 20);
            $table->date('next_review_date')->default(DB::raw('CURRENT_DATE'));
            $table->float('ease_factor')->default(2.5);   // REAL en PostgreSQL
            $table->integer('interval_days')->default(0);
            $table->integer('repetitions')->default(0);
            $table->timestamps();

            // Clave foránea a users con CASCADE
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            // Unicidad por usuario + elemento
            $table->unique(['user_id', 'item_id', 'item_type'], 'uq_user_progress');
        });

        // CHECK constraint polimórfico
        DB::statement("ALTER TABLE user_progress ADD CONSTRAINT chk_user_progress_item_type CHECK (item_type IN ('entity', 'compound'))");

        // Índice crítico de rendimiento (RNF: <200ms para lote de repaso)
        DB::statement('CREATE INDEX idx_user_progress_due ON user_progress(user_id, next_review_date)');

        // Índice morfológico para búsquedas inversas
        DB::statement('CREATE INDEX idx_user_progress_morph ON user_progress(item_type, item_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_progress');
    }
};
