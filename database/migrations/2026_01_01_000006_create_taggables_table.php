<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de la tabla taggables (polimórfica).
 * Permite asignar etiquetas semánticas tanto a 'entity' como a 'compound'
 * sin duplicar la tabla de tags. La PK es compuesta (tag_id, taggable_id, taggable_type).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taggables', function (Blueprint $table) {
            $table->unsignedBigInteger('tag_id');
            $table->unsignedBigInteger('taggable_id');
            $table->string('taggable_type', 20);

            // Clave primaria compuesta
            $table->primary(['tag_id', 'taggable_id', 'taggable_type']);

            // Clave foránea a tags con CASCADE
            $table->foreign('tag_id')
                  ->references('id')->on('tags')
                  ->onDelete('cascade');
        });

        // CHECK constraint: solo 'entity' o 'compound'
        DB::statement("ALTER TABLE taggables ADD CONSTRAINT chk_taggables_type CHECK (taggable_type IN ('entity', 'compound'))");

        // Índice morfológico para búsquedas por tipo+id
        DB::statement('CREATE INDEX idx_taggables_morph ON taggables(taggable_type, taggable_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
