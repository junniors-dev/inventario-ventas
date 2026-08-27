<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Carga la base de datos con datos de demostración.
     *
     * El orden importa: los productos necesitan categorías y las ventas
     * necesitan usuarios y productos.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategoriaSeeder::class,
            ProductoSeeder::class,
            VentaSeeder::class,
        ]);
    }
}
