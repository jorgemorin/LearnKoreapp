<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de la tabla study_logs.
 * Registro histórico INMUTABLE de cada intento de repaso.
 * NUNCA se actualiza ni se borra: solo se insertan registros.
 * Esto garantiza la fiabilidad de la analítica educativa.
 *
 * Nota: la inmutabilidad se garantiza a nivel de servicio (StudyLog solo
 * expone create(), nunca update() ni delete()), no a nivel de BD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_type', 20);
            $table->boolean('is_correct');
            $table->integer('time_taken_ms');  // Tiempo en milisegundos
            $table->timestamp('created_at')->useCurrent();
            // Sin updated_at — este registro nunca se modifica

            // Clave foránea a users con CASCADE
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });

        // CHECK constraint polimórfico
        DB::statement("ALTER TABLE study_logs ADD CONSTRAINT chk_study_logs_item_type CHECK (item_type IN ('entity', 'compound'))");

        // Índice para analítica por usuario en el tiempo
        DB::statement('CREATE INDEX idx_study_logs_user ON study_logs(user_id, created_at)');

        // Índice morfológico para tasa de acierto por elemento
        DB::statement('CREATE INDEX idx_study_logs_morph ON study_logs(item_type, item_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('study_logs');
    }
};
