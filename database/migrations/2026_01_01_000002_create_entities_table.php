<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de la tabla entities (Diccionario Global).
 * Almacena raíces, partículas y palabras del coreano sin duplicados.
 * La unicidad se garantiza con el par (text, type).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('text', 255);
            $table->string('type', 30);
            $table->text('meaning');
            $table->string('status', 20)->default('pending_review');
            $table->timestamps();

            // Unicidad de la combinación texto + tipo
            $table->unique(['text', 'type']);
        });

        // CHECK constraints según el DDL original
        DB::statement("ALTER TABLE entities ADD CONSTRAINT chk_entities_type CHECK (type IN ('root', 'particle', 'word'))");
        DB::statement("ALTER TABLE entities ADD CONSTRAINT chk_entities_status CHECK (status IN ('pending_review', 'verified'))");

        // Índices de rendimiento
        DB::statement('CREATE INDEX idx_entities_status ON entities(status)');
        DB::statement('CREATE INDEX idx_entities_type ON entities(type)');
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
