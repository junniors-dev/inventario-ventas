<?php

use App\Actions\AnularVenta;
use App\Actions\ObtenerMetricasDashboard;
use App\Actions\RegistrarVenta;
use App\Enums\MetodoPago;
use App\Enums\Role;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => Role::Admin]);
    $categoria = Categoria::create(['nombre' => 'Bebidas']);

    $this->coca = Producto::create([
        'categoria_id' => $categoria->id,
        'nombre' => 'Coca-Cola 500ml',
        'precio' => 3.00,
        'stock' => 40,
        'stock_minimo' => 12,
    ]);

    $this->escaso = Producto::create([
        'categoria_id' => $categoria->id,
        'nombre' => 'Inca Kola 1.5L',
        'precio' => 6.50,
        'stock' => 2,
        'stock_minimo' => 8,
    ]);
});

test('un vendedor no puede ver el dashboard', function () {
    $vendedor = User::factory()->create(['role' => Role::Vendedor]);

    $this->actingAs($vendedor)
        ->get(route('dashboard'))
        ->assertForbidden();
});

test('el admin ve el dashboard', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ventas del mes')
        ->assertSee('Productos con stock bajo');
});

test('las métricas cuentan los productos con stock bajo', function () {
    $metricas = (new ObtenerMetricasDashboard)->handle();

    expect($metricas['stockBajo'])->toBe(1)
        ->and($metricas['productosActivos'])->toBe(2)
        ->and($metricas['productosStockBajo']->first()->nombre)->toBe('Inca Kola 1.5L');
});

test('las métricas suman las ventas del mes', function () {
    (new RegistrarVenta)->handle($this->admin,
        [['producto_id' => $this->coca->id, 'cantidad' => 4]], MetodoPago::Efectivo);

    $metricas = (new ObtenerMetricasDashboard)->handle();

    expect($metricas['ventasMes'])->toBe(12.0)
        ->and($metricas['ventasHoy'])->toBe(1)
        ->and($metricas['totalHoy'])->toBe(12.0);
});

test('las ventas anuladas no cuentan en las métricas', function () {
    $venta = (new RegistrarVenta)->handle($this->admin,
        [['producto_id' => $this->coca->id, 'cantidad' => 4]], MetodoPago::Efectivo);

    expect((new ObtenerMetricasDashboard)->handle()['ventasMes'])->toBe(12.0);

    (new AnularVenta)->handle($venta);

    expect((new ObtenerMetricasDashboard)->handle()['ventasMes'])->toBe(0.0);
});

test('el ranking de más vendidos ordena por unidades', function () {
    (new RegistrarVenta)->handle($this->admin, [
        ['producto_id' => $this->coca->id, 'cantidad' => 10],
        ['producto_id' => $this->escaso->id, 'cantidad' => 2],
    ], MetodoPago::Yape);

    $masVendidos = (new ObtenerMetricasDashboard)->handle()['masVendidos'];

    expect($masVendidos)->toHaveCount(2)
        ->and($masVendidos->first()->nombre)->toBe('Coca-Cola 500ml')
        ->and((int) $masVendidos->first()->unidades)->toBe(10);
});

test('la serie mensual siempre devuelve ocho meses', function () {
    $serie = (new ObtenerMetricasDashboard)->handle()['serieMensual'];

    expect($serie)->toHaveCount(8)
        ->and($serie->last()['total'])->toBe(0.0);
});
