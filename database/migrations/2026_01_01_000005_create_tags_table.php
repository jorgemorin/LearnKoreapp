<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de la tabla tags.
 * Etiquetas semánticas situacionales (Restaurante, Saludos, Educación, etc.)
 * compartidas en el diccionario global.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
