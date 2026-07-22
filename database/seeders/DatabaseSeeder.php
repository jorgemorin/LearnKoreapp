<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder principal de la base de datos.
 * Llama a todos los seeders en el orden correcto.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TagSeeder::class,      // Fase A: taxonomía estándar de 24 etiquetas
        ]);
    }
}
