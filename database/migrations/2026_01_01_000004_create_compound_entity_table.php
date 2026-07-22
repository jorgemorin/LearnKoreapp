<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de la tabla pivot compound_entity.
 * Relaciona compuestos con sus entidades morfológicas (raíces y partículas)
 * indicando el orden de posición de cada morfema dentro del compuesto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compound_entity', function (Blueprint $table) {
            $table->unsignedBigInteger('compound_id');
            $table->unsignedBigInteger('entity_id');
            $table->smallInteger('position_order');

            // Clave primaria compuesta
            $table->primary(['compound_id', 'entity_id']);

            // Claves foráneas con CASCADE DELETE
            $table->foreign('compound_id')
                  ->references('id')->on('compounds')
                  ->onDelete('cascade');

            $table->foreign('entity_id')
                  ->references('id')->on('entities')
                  ->onDelete('cascade');
        });

        // Índice para búsquedas inversas (entity → compounds)
        DB::statement('CREATE INDEX idx_compound_entity_entity ON compound_entity(entity_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('compound_entity');
    }
};
