<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = Categoria::pluck('id', 'nombre');

        // [categoría, nombre, precio, stock, stock mínimo]
        $productos = [
            // Abarrotes
            ['Abarrotes', 'Arroz Costeño 1kg', 4.50, 8, 10],
            ['Abarrotes', 'Arroz Paisana 5kg', 21.90, 34, 8],
            ['Abarrotes', 'Aceite Primor 1L', 9.90, 42, 10],
            ['Abarrotes', 'Aceite Cocinero 900ml', 8.50, 27, 10],
            ['Abarrotes', 'Fideos Don Vittorio 500g', 3.80, 55, 15],
            ['Abarrotes', 'Fideos Molitalia 250g', 2.20, 61, 20],
            ['Abarrotes', 'Atún Florida en aceite', 5.50, 18, 12],
            ['Abarrotes', 'Atún Real filete', 6.90, 9, 10],
            ['Abarrotes', 'Azúcar rubia 1kg', 3.90, 7, 10],
            ['Abarrotes', 'Sal de mesa Emsal 1kg', 1.80, 48, 15],
            ['Abarrotes', 'Lentejas 500g', 4.20, 30, 10],
            ['Abarrotes', 'Frijol canario 500g', 6.50, 22, 10],
            ['Abarrotes', 'Harina Blanca Flor 1kg', 4.80, 26, 10],
            ['Abarrotes', 'Avena Quaker 340g', 5.20, 19, 10],
            ['Abarrotes', 'Salsa de tomate Pomarola', 3.40, 33, 12],

            // Bebidas
            ['Bebidas', 'Coca-Cola 500ml', 3.00, 72, 20],
            ['Bebidas', 'Coca-Cola 3L', 10.50, 24, 8],
            ['Bebidas', 'Inca Kola 1.5L', 6.50, 5, 12],
            ['Bebidas', 'Inca Kola 500ml', 3.00, 58, 20],
            ['Bebidas', 'Agua San Luis 625ml', 1.50, 96, 25],
            ['Bebidas', 'Agua Cielo 2.5L', 4.20, 31, 10],
            ['Bebidas', 'Sprite 1.5L', 5.80, 27, 10],
            ['Bebidas', 'Frugos Del Valle 1L', 5.50, 23, 10],
            ['Bebidas', 'Cifrut naranja 3L', 7.90, 16, 8],
            ['Bebidas', 'Pilsen Callao 355ml', 4.50, 60, 24],
            ['Bebidas', 'Cusqueña dorada 310ml', 5.20, 44, 20],

            // Limpieza
            ['Limpieza', 'Detergente Bolívar 900g', 8.50, 4, 8],
            ['Limpieza', 'Detergente Ariel 780g', 12.90, 18, 6],
            ['Limpieza', 'Lejía Clorox 1L', 4.60, 25, 10],
            ['Limpieza', 'Jabón Bolívar barra', 2.80, 40, 15],
            ['Limpieza', 'Papel higiénico Elite x4', 6.90, 35, 12],
            ['Limpieza', 'Papel toalla Suave x2', 7.50, 14, 8],
            ['Limpieza', 'Lavavajilla Ayudín 360g', 5.40, 29, 10],
            ['Limpieza', 'Esponja verde x3', 3.20, 37, 12],
            ['Limpieza', 'Pinesol 900ml', 9.80, 11, 8],

            // Snacks
            ['Snacks', 'Galletas Soda Field', 1.50, 88, 25],
            ['Snacks', 'Galletas Oreo 108g', 3.20, 46, 15],
            ['Snacks', 'Chocolate Sublime', 2.00, 67, 20],
            ['Snacks', 'Chocolate Princesa', 2.50, 52, 20],
            ['Snacks', 'Papas Lays clásicas', 4.50, 39, 15],
            ['Snacks', 'Chizitos Karinto', 1.80, 3, 15],
            ['Snacks', 'Doritos queso 145g', 5.90, 21, 10],
            ['Snacks', 'Chifles Inka Chips', 4.20, 17, 10],

            // Lácteos
            ['Lácteos', 'Leche Gloria evaporada', 4.20, 64, 20],
            ['Lácteos', 'Leche Laive light', 4.80, 28, 12],
            ['Lácteos', 'Yogurt Gloria 1L fresa', 7.50, 20, 10],
            ['Lácteos', 'Queso fresco 250g', 9.90, 6, 8],
            ['Lácteos', 'Mantequilla Laive 200g', 8.70, 13, 8],
            ['Lácteos', 'Leche condensada Nestlé', 6.40, 24, 10],

            // Cuidado personal
            ['Cuidado personal', 'Shampoo Head & Shoulders 375ml', 21.90, 12, 6],
            ['Cuidado personal', 'Jabón Protex 110g', 3.50, 43, 15],
            ['Cuidado personal', 'Pasta dental Colgate 90g', 6.80, 30, 12],
            ['Cuidado personal', 'Desodorante Rexona 150ml', 14.50, 9, 8],
            ['Cuidado personal', 'Papel facial Elite 100u', 4.90, 26, 10],
        ];

        foreach ($productos as [$categoria, $nombre, $precio, $stock, $minimo]) {
            Producto::updateOrCreate(
                ['nombre' => $nombre],
                [
                    'categoria_id' => $categorias[$categoria],
                    'precio' => $precio,
                    'stock' => $stock,
                    'stock_minimo' => $minimo,
                ],
            );
        }
    }
}
