<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de Fase A: estandarización de etiquetas.
 *
 * Añade tres columnas a la tabla `tags`:
 *   - layer: capa semántica (grammar | register | thematic)
 *   - is_standard: indica si el tag pertenece al catálogo oficial
 *   - is_visible_default: si aparece en filtros principales (true) o en "avanzados" (false)
 *   - description: descripción opcional del tag
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->string('layer', 20)->nullable()->default(null)
                  ->comment('grammar | register | thematic');
            $table->boolean('is_standard')->default(false)
                  ->comment('Pertenece al catálogo oficial de etiquetas');
            $table->boolean('is_visible_default')->default(true)
                  ->comment('Visible en filtros principales (false = filtros avanzados)');
            $table->string('description', 255)->nullable()->default(null);
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn(['layer', 'is_standard', 'is_visible_default', 'description']);
        });
    }
};
