<?php

use App\Enums\Role;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Database\Seeders\CategoriaSeeder;
use Database\Seeders\ProductoSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\VentaSeeder;

test('la factory de productos crea productos válidos', function () {
    $producto = Producto::factory()->create();

    expect($producto->precio)->toBeGreaterThan(0)
        ->and($producto->stock)->toBeGreaterThan($producto->stock_minimo)
        ->and($producto->categoria)->not->toBeNull();
});

test('el estado stockBajo crea un producto bajo el mínimo', function () {
    $producto = Producto::factory()->stockBajo()->create();

    expect($producto->stock)->toBeLessThanOrEqual($producto->stock_minimo);
    expect(Producto::stockBajo()->count())->toBe(1);
});

test('el estado admin crea un usuario administrador', function () {
    expect(User::factory()->admin()->create()->isAdmin())->toBeTrue()
        ->and(User::factory()->create()->isVendedor())->toBeTrue();
});

test('el seeder de usuarios crea las cuentas de demostración', function () {
    $this->seed(UserSeeder::class);

    $admin = User::where('email', 'admin@bodega.pe')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->role)->toBe(Role::Admin)
        ->and(User::where('role', Role::Vendedor)->count())->toBe(2);
});

test('el seeder es idempotente y no duplica registros', function () {
    $this->seed(UserSeeder::class);
    $this->seed(CategoriaSeeder::class);

    $usuarios = User::count();
    $categorias = Categoria::count();

    // Ejecutarlo dos veces no debe crear duplicados.
    $this->seed(UserSeeder::class);
    $this->seed(CategoriaSeeder::class);

    expect(User::count())->toBe($usuarios)
        ->and(Categoria::count())->toBe($categorias);
});

test('el seeder completo genera un historial de ventas usable', function () {
    $this->seed([UserSeeder::class, CategoriaSeeder::class, ProductoSeeder::class, VentaSeeder::class]);

    expect(Producto::count())->toBeGreaterThan(40)
        ->and(Producto::stockBajo()->count())->toBeGreaterThan(0)
        ->and(Venta::count())->toBeGreaterThan(50);

    // Todas las ventas tienen al menos una línea de detalle.
    expect(Venta::doesntHave('detalles')->count())->toBe(0);

    // El histórico cubre varios meses para que la gráfica tenga datos.
    $meses = Venta::get(['created_at'])
        ->groupBy(fn (Venta $v) => $v->created_at->format('Y-m'))
        ->count();

    expect($meses)->toBeGreaterThanOrEqual(6);
});

test('los códigos de venta del seeder no se repiten', function () {
    $this->seed([UserSeeder::class, CategoriaSeeder::class, ProductoSeeder::class, VentaSeeder::class]);

    expect(Venta::distinct('codigo')->count('codigo'))->toBe(Venta::count());
});
