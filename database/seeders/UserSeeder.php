<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder de usuarios de prueba para desarrollo.
 * Crea un usuario administrador y un usuario estándar.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario administrador
        User::firstOrCreate(
            ['email' => 'admin@learnkoreapp.com'],
            [
                'name'     => 'Administrador',
                'password' => Hash::make('password'),
                'role'     => User::ROLE_ADMIN,
            ]
        );

        // Usuario estándar de prueba
        User::firstOrCreate(
            ['email' => 'user@learnkoreapp.com'],
            [
                'name'     => 'Usuario de Prueba',
                'password' => Hash::make('password'),
                'role'     => User::ROLE_USER,
            ]
        );

        $this->command->info('✅ Usuarios de prueba creados:');
        $this->command->info('   admin@learnkoreapp.com / password (rol: admin)');
        $this->command->info('   user@learnkoreapp.com  / password (rol: user)');
    }
}
