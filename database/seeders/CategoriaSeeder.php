<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Abarrotes', 'descripcion' => 'Productos secos y de despensa'],
            ['nombre' => 'Bebidas', 'descripcion' => 'Gaseosas, aguas y jugos'],
            ['nombre' => 'Limpieza', 'descripcion' => 'Artículos de aseo y hogar'],
            ['nombre' => 'Snacks', 'descripcion' => 'Galletas, dulces y piqueos'],
            ['nombre' => 'Lácteos', 'descripcion' => 'Leche, yogurt y quesos'],
            ['nombre' => 'Cuidado personal', 'descripcion' => 'Higiene y cuidado del cuerpo'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::updateOrCreate(['nombre' => $categoria['nombre']], $categoria);
        }
    }
}
