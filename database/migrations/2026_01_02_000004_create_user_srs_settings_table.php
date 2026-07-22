<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración Fase B.3: tabla de configuración SRS por usuario.
 *
 * Permite a cada usuario personalizar su experiencia de aprendizaje.
 * Todos los valores tienen defaults razonables basados en Anki estándar.
 *
 * Columnas:
 *   learning_steps       Pasos de learning en minutos, separados por espacio ("1 10")
 *   relearning_steps     Pasos de relearning en minutos ("10")
 *   graduating_interval  Días para graduarse de Learning a Young (first interval)
 *   easy_interval        Días de intervalo al pulsar "Fácil" en una carta New
 *   easy_bonus           Multiplicador extra para el rating "Fácil" en Review
 *   interval_modifier    Multiplicador global del usuario (ajuste fino del scheduler)
 *   max_interval         Intervalo máximo en días (100 años por defecto)
 *   new_cards_per_day    Límite de tarjetas nuevas por día
 *   review_cards_per_day Límite de tarjetas de revisión por día
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_srs_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->primary();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->string('learning_steps', 100)->default('1 10')
                  ->comment('Pasos en minutos separados por espacio');
            $table->string('relearning_steps', 100)->default('10')
                  ->comment('Pasos de reaprendizaje en minutos');
            $table->unsignedSmallInteger('graduating_interval')->default(1)
                  ->comment('Días al graduarse de Learning (primer Review interval)');
            $table->unsignedSmallInteger('easy_interval')->default(4)
                  ->comment('Días al pulsar Fácil en carta New');
            $table->float('easy_bonus')->default(1.3)
                  ->comment('Multiplicador extra para Fácil en Review');
            $table->float('interval_modifier')->default(1.0)
                  ->comment('Multiplicador global del usuario');
            $table->unsignedInteger('max_interval')->default(36500)
                  ->comment('Intervalo máximo en días');
            $table->unsignedSmallInteger('new_cards_per_day')->default(20);
            $table->unsignedSmallInteger('review_cards_per_day')->default(200);

            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_srs_settings');
    }
};
