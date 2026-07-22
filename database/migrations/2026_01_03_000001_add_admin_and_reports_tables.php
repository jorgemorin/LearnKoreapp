<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración Fase D.1: Sistema de Reportes y Admin.
 *
 * - users: columna `is_active` para activar/desactivar cuentas
 * - user_reports: tickets de soporte/reporte enviados por los usuarios
 * - admin_actions_log: auditoría de todas las acciones admin
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Columna is_active en users ────────────────────────────────────
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
        });

        // ── 2. Tabla de reportes de usuarios ─────────────────────────────────
        Schema::create('user_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Categoría del reporte
            $table->string('category', 50);  // 'error_traduccion','error_hangul','contenido_inapropiado','bug','sugerencia','otro'

            $table->text('description');

            // Item relacionado (opcional) — morphable
            $table->unsignedBigInteger('related_item_id')->nullable();
            $table->string('related_item_type', 50)->nullable(); // 'compound' | 'entity'

            // Estado del ticket
            $table->string('status', 30)->default('pending'); // pending|reviewing|resolved|dismissed

            // Nota del administrador al resolver
            $table->text('admin_notes')->nullable();

            // Admin que gestionó el reporte
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });

        // ── 3. Log de acciones admin ──────────────────────────────────────────
        Schema::create('admin_actions_log', function (Blueprint $table) {
            $table->id();

            $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();

            // Tipo de acción (ej. 'user.deactivate', 'tag.merge', 'report.resolve')
            $table->string('action_type', 80);

            // Target de la acción (morphable)
            $table->string('target_type', 50)->nullable(); // 'user'|'compound'|'tag'|'report'
            $table->unsignedBigInteger('target_id')->nullable();

            // Payload JSON con los datos relevantes (antes/después si aplica)
            $table->json('payload')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['admin_id', 'created_at']);
            $table->index(['action_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_actions_log');
        Schema::dropIfExists('user_reports');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
