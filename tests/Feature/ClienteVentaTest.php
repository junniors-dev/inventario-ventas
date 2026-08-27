<?php

use App\Actions\RegistrarVenta;
use App\Enums\MetodoPago;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;

beforeEach(function () {
    $this->vendedor = User::factory()->create();
    $categoria = Categoria::create(['nombre' => 'Bebidas']);

    $this->producto = Producto::create([
        'categoria_id' => $categoria->id,
        'nombre' => 'Agua San Luis',
        'precio' => 2.00,
        'stock' => 100,
        'stock_minimo' => 10,
    ]);
});

test('se puede registrar una venta con los datos del cliente', function () {
    $this->actingAs($this->vendedor)->post(route('ventas.store'), [
        'metodo_pago' => MetodoPago::Efectivo->value,
        'cliente_nombre' => 'Distribuidora El Sol SAC',
        'cliente_documento' => '20481234567',
        'lineas' => [['producto_id' => $this->producto->id, 'cantidad' => 3]],
    ])->assertRedirect();

    $venta = Venta::first();

    expect($venta->cliente_nombre)->toBe('Distribuidora El Sol SAC')
        ->and($venta->cliente_documento)->toBe('20481234567');
});

test('el cliente es opcional: la venta a público general funciona igual', function () {
    $this->actingAs($this->vendedor)->post(route('ventas.store'), [
        'metodo_pago' => MetodoPago::Efectivo->value,
        'lineas' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
    ])->assertRedirect();

    $venta = Venta::first();

    expect($venta->cliente_nombre)->toBeNull()
        ->and($venta->cliente_documento)->toBeNull();
});

test('el comprobante muestra los datos del cliente', function () {
    $venta = (new RegistrarVenta)->handle($this->vendedor,
        [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        MetodoPago::Yape,
        ['nombre' => 'Rosa Flores', 'documento' => '45678912']);

    $this->actingAs($this->vendedor)
        ->get(route('ventas.show', $venta))
        ->assertOk()
        ->assertSee('Rosa Flores')
        ->assertSee('45678912');
});

test('el listado marca las ventas sin cliente como público general', function () {
    (new RegistrarVenta)->handle($this->vendedor,
        [['producto_id' => $this->producto->id, 'cantidad' => 1]], MetodoPago::Efectivo);

    $this->actingAs($this->vendedor)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee('Público general');
});

test('se puede buscar una venta por el nombre del cliente', function () {
    $conCliente = (new RegistrarVenta)->handle($this->vendedor,
        [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        MetodoPago::Efectivo, ['nombre' => 'Rosa Flores']);

    $sinCliente = (new RegistrarVenta)->handle($this->vendedor,
        [['producto_id' => $this->producto->id, 'cantidad' => 1]], MetodoPago::Efectivo);

    $this->actingAs($this->vendedor)
        ->get(route('ventas.index', ['buscar' => 'Rosa']))
        ->assertOk()
        ->assertSee($conCliente->codigo)
        ->assertDontSee($sinCliente->codigo);
});

test('se puede buscar una venta por el documento del cliente', function () {
    $venta = (new RegistrarVenta)->handle($this->vendedor,
        [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        MetodoPago::Efectivo, ['nombre' => 'Rosa Flores', 'documento' => '45678912']);

    $this->actingAs($this->vendedor)
        ->get(route('ventas.index', ['buscar' => '45678912']))
        ->assertOk()
        ->assertSee($venta->codigo);
});

test('el cliente aparece en la exportación CSV', function () {
    (new RegistrarVenta)->handle($this->vendedor,
        [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        MetodoPago::Efectivo, ['nombre' => 'Rosa Flores', 'documento' => '45678912']);

    $respuesta = $this->actingAs($this->vendedor)->get(route('ventas.exportar'));

    ob_start();
    $respuesta->sendContent();
    $csv = ob_get_clean();

    expect($csv)->toContain('Cliente;Documento')
        ->and($csv)->toContain('Rosa Flores')
        ->and($csv)->toContain('45678912');
});

test('el ticket PDF se genera con los datos del cliente', function () {
    $venta = (new RegistrarVenta)->handle($this->vendedor,
        [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        MetodoPago::Efectivo, ['nombre' => 'Rosa Flores', 'documento' => '45678912']);

    $this->actingAs($this->vendedor)
        ->get(route('ventas.ticket', $venta))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
