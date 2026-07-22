<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migración Fase C.1: índices de búsqueda y filtrado para la colección.
 *
 * - compounds: índice en full_text y translation para búsqueda rápida
 * - user_progress: índice compuesto (user_id, card_state, next_review_date)
 *   para los filtros combinados sin full-scan
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compounds', function (Blueprint $table) {
            $table->index('full_text',   'idx_compounds_full_text');
            $table->index('translation', 'idx_compounds_translation');
        });

        Schema::table('user_progress', function (Blueprint $table) {
            $table->index(
                ['user_id', 'card_state', 'next_review_date'],
                'idx_up_user_state_date'
            );
        });
    }

    public function down(): void
    {
        Schema::table('compounds', function (Blueprint $table) {
            $table->dropIndex('idx_compounds_full_text');
            $table->dropIndex('idx_compounds_translation');
        });

        Schema::table('user_progress', function (Blueprint $table) {
            $table->dropIndex('idx_up_user_state_date');
        });
    }
};
