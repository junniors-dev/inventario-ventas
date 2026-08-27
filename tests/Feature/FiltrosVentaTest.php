<?php

use App\Actions\AnularVenta;
use App\Actions\RegistrarVenta;
use App\Enums\MetodoPago;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Junni Díaz']);
    $this->maria = User::factory()->create(['name' => 'María Quispe']);
    $this->carlos = User::factory()->create(['name' => 'Carlos Ramos']);

    $categoria = Categoria::create(['nombre' => 'Bebidas']);
    $this->producto = Producto::create([
        'categoria_id' => $categoria->id,
        'nombre' => 'Agua San Luis',
        'precio' => 2.00,
        'stock' => 500,
        'stock_minimo' => 20,
    ]);
});

function venta(User $usuario, MetodoPago $metodo = MetodoPago::Efectivo, int $cantidad = 1): Venta
{
    return (new RegistrarVenta)->handle($usuario,
        [['producto_id' => test()->producto->id, 'cantidad' => $cantidad]], $metodo);
}

test('filtra las ventas por vendedor', function () {
    $deMaria = venta($this->maria);
    $deCarlos = venta($this->carlos);

    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['vendedor' => $this->maria->id]))
        ->assertOk()
        ->assertSee($deMaria->codigo)
        ->assertDontSee($deCarlos->codigo);
});

test('filtra las ventas por método de pago', function () {
    $enYape = venta($this->maria, MetodoPago::Yape);
    $enEfectivo = venta($this->maria, MetodoPago::Efectivo);

    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['metodo_pago' => 'yape']))
        ->assertOk()
        ->assertSee($enYape->codigo)
        ->assertDontSee($enEfectivo->codigo);
});

test('filtra las ventas por estado', function () {
    $vigente = venta($this->maria);
    $anulada = venta($this->maria);
    (new AnularVenta)->handle($anulada);

    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['estado' => 'anulada']))
        ->assertOk()
        ->assertSee($anulada->codigo)
        ->assertDontSee($vigente->codigo);
});

test('filtra las ventas por rango de fechas', function () {
    $antigua = venta($this->maria);
    // created_at no es asignable en masa: hay que forzarlo.
    $antigua->forceFill(['created_at' => now()->subMonth()])->save();
    $reciente = venta($this->maria);

    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['desde' => now()->startOfDay()->toDateString()]))
        ->assertOk()
        ->assertSee($reciente->codigo)
        ->assertDontSee($antigua->codigo);
});

test('busca una venta por su comprobante', function () {
    $buscada = venta($this->maria);
    $otra = venta($this->carlos);

    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['buscar' => $buscada->codigo]))
        ->assertOk()
        ->assertSee($buscada->codigo)
        ->assertDontSee($otra->codigo);
});

test('el resumen suma solo las ventas filtradas y vigentes', function () {
    venta($this->maria, MetodoPago::Yape, 3);   // S/ 6.00
    venta($this->maria, MetodoPago::Yape, 2);   // S/ 4.00
    venta($this->carlos, MetodoPago::Efectivo, 5);

    $anulada = venta($this->maria, MetodoPago::Yape, 10);
    (new AnularVenta)->handle($anulada);

    // Filtrando por María + Yape: 6.00 + 4.00, sin contar la anulada.
    $this->actingAs($this->admin)
        ->get(route('ventas.index', ['vendedor' => $this->maria->id, 'metodo_pago' => 'yape']))
        ->assertOk()
        ->assertSee('S/ 10.00')
        ->assertSee('2 ventas');
});

test('los filtros no permiten a un vendedor ver ventas ajenas', function () {
    $ajena = venta($this->carlos);

    // Aunque pida explícitamente las de Carlos, solo ve las suyas.
    $this->actingAs($this->maria)
        ->get(route('ventas.index', ['vendedor' => $this->carlos->id]))
        ->assertOk()
        ->assertDontSee($ajena->codigo);
});
