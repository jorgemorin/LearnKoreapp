<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración de la tabla users.
 * Extiende la estructura estándar de Laravel añadiendo el campo 'role'
 * con los valores permitidos 'user' y 'admin' tal como indica el esquema SQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 255)->unique();
            $table->string('password');          // password_hash en el DDL SQL
            $table->string('role', 20)->default('user');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // CHECK constraint: solo 'user' o 'admin'
        DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_role CHECK (role IN ('user', 'admin'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
