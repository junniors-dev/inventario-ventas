<?php

use App\Actions\ObtenerMetricasDashboard;
use App\Actions\RegistrarVenta;
use App\Enums\MetodoPago;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Junni Díaz']);
    $this->maria = User::factory()->create(['name' => 'María Quispe']);

    $categoria = Categoria::create(['nombre' => 'Bebidas']);
    $this->producto = Producto::create([
        'categoria_id' => $categoria->id,
        'nombre' => 'Agua San Luis',
        'precio' => 10.00,
        'stock' => 500,
        'stock_minimo' => 20,
    ]);
});

function vender(User $usuario, MetodoPago $metodo, int $cantidad)
{
    return (new RegistrarVenta)->handle($usuario,
        [['producto_id' => test()->producto->id, 'cantidad' => $cantidad]], $metodo);
}

test('el ticket promedio divide lo vendido entre el número de ventas', function () {
    vender($this->maria, MetodoPago::Efectivo, 2);  // S/ 20
    vender($this->maria, MetodoPago::Efectivo, 4);  // S/ 40

    expect((new ObtenerMetricasDashboard)->handle()['ticketPromedio'])->toBe(30.0);
});

test('el ticket promedio es cero cuando no hay ventas', function () {
    expect((new ObtenerMetricasDashboard)->handle()['ticketPromedio'])->toBe(0.0);
});

test('el reparto por método de pago suma lo cobrado con cada uno', function () {
    vender($this->maria, MetodoPago::Yape, 3);      // S/ 30
    vender($this->maria, MetodoPago::Yape, 1);      // S/ 10
    vender($this->admin, MetodoPago::Efectivo, 2);  // S/ 20

    $porMetodo = (new ObtenerMetricasDashboard)->handle()['porMetodoPago']
        ->keyBy('etiqueta');

    expect($porMetodo['Yape']['total'])->toBe(40.0)
        ->and($porMetodo['Efectivo']['total'])->toBe(20.0)
        // Los métodos sin uso se listan en cero para que la gráfica no cambie de forma.
        ->and($porMetodo['Plin']['total'])->toBe(0.0)
        ->and($porMetodo)->toHaveCount(4);
});

test('el ranking ordena a los vendedores por lo facturado', function () {
    vender($this->maria, MetodoPago::Efectivo, 5);  // S/ 50
    vender($this->admin, MetodoPago::Efectivo, 2);  // S/ 20

    $ranking = (new ObtenerMetricasDashboard)->handle()['rankingVendedores'];

    expect($ranking)->toHaveCount(2)
        ->and($ranking->first()['nombre'])->toBe('María Quispe')
        ->and($ranking->first()['total'])->toBe(50.0)
        ->and($ranking->first()['ventas'])->toBe(1);
});

test('el dashboard muestra las métricas nuevas', function () {
    vender($this->maria, MetodoPago::Yape, 2);

    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ticket promedio')
        ->assertSee('Cobros por método de pago')
        ->assertSee('Ranking de vendedores')
        ->assertSee('María Quispe');
});
