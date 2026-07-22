<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración Fase B.1: ampliar user_progress con campos del algoritmo Anki completo.
 *
 * - card_state:          ciclo de vida de la tarjeta (new → learning → young → mature)
 * - lapses:              número de fallos en fase Review (útil para diagnóstico)
 * - learning_step_index: paso actual dentro de la secuencia de learning/relearning
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_progress', function (Blueprint $table) {
            $table->string('card_state', 20)->default('new')
                  ->comment('new | learning | young | mature | relearning | suspended')
                  ->after('repetitions');

            $table->unsignedSmallInteger('lapses')->default(0)
                  ->comment('Fallos acumulados en fase Review')
                  ->after('card_state');

            $table->unsignedTinyInteger('learning_step_index')->default(0)
                  ->comment('Índice del paso actual en la secuencia de learning')
                  ->after('lapses');
        });
    }

    public function down(): void
    {
        Schema::table('user_progress', function (Blueprint $table) {
            $table->dropColumn(['card_state', 'lapses', 'learning_step_index']);
        });
    }
};
