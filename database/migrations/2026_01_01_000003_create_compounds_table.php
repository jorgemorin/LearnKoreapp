<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de la tabla compounds (Diccionario Global).
 * Almacena palabras aglutinadas coreanas completas (compuestos de morfemas).
 * El campo full_text tiene restricción UNIQUE.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compounds', function (Blueprint $table) {
            $table->id();
            $table->string('full_text', 255)->unique();
            $table->text('translation');
            $table->string('status', 20)->default('pending_review');
            $table->timestamps();
        });

        // CHECK constraint según el DDL original
        DB::statement("ALTER TABLE compounds ADD CONSTRAINT chk_compounds_status CHECK (status IN ('pending_review', 'verified'))");

        // Índice de rendimiento
        DB::statement('CREATE INDEX idx_compounds_status ON compounds(status)');
    }

    public function down(): void
    {
        Schema::dropIfExists('compounds');
    }
};
