<?php

use App\Actions\RegistrarVenta;
use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\Role;
use App\Exceptions\StockInsuficienteException;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;

beforeEach(function () {
    $this->vendedor = User::factory()->create(['role' => Role::Vendedor]);
    $categoria = Categoria::create(['nombre' => 'Bebidas']);

    $this->producto = Producto::create([
        'categoria_id' => $categoria->id,
        'nombre' => 'Inca Kola 1.5L',
        'precio' => 6.50,
        'stock' => 10,
        'stock_minimo' => 3,
    ]);
});

test('un vendedor puede acceder a la pantalla de nueva venta', function () {
    $this->actingAs($this->vendedor)
        ->get(route('ventas.create'))
        ->assertOk()
        ->assertSee('Inca Kola 1.5L');
});

test('registrar una venta descuenta el stock y calcula el total', function () {
    $this->actingAs($this->vendedor)
        ->post(route('ventas.store'), [
            'metodo_pago' => MetodoPago::Yape->value,
            'lineas' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 3],
            ],
        ])
        ->assertRedirect();

    expect($this->producto->fresh()->stock)->toBe(7);

    $venta = Venta::first();
    expect($venta->total)->toBe('19.50')
        ->and($venta->metodo_pago)->toBe(MetodoPago::Yape)
        ->and($venta->estado)->toBe(EstadoVenta::Completada)
        ->and($venta->detalles)->toHaveCount(1);
});

test('no permite vender más stock del disponible', function () {
    $this->actingAs($this->vendedor)
        ->post(route('ventas.store'), [
            'metodo_pago' => MetodoPago::Efectivo->value,
            'lineas' => [
                ['producto_id' => $this->producto->id, 'cantidad' => 11],
            ],
        ])
        ->assertSessionHasErrors('lineas');

    // El stock no cambió y no se creó ninguna venta.
    expect($this->producto->fresh()->stock)->toBe(10);
    $this->assertDatabaseCount('ventas', 0);
});

test('la venta debe tener al menos un producto', function () {
    $this->actingAs($this->vendedor)
        ->post(route('ventas.store'), [
            'metodo_pago' => MetodoPago::Efectivo->value,
            'lineas' => [],
        ])
        ->assertSessionHasErrors('lineas');
});

test('el precio unitario se congela al momento de la venta', function () {
    $this->actingAs($this->vendedor)->post(route('ventas.store'), [
        'metodo_pago' => MetodoPago::Efectivo->value,
        'lineas' => [['producto_id' => $this->producto->id, 'cantidad' => 2]],
    ]);

    // El precio del producto sube después de la venta.
    $this->producto->update(['precio' => 9.90]);

    $detalle = Venta::first()->detalles()->first();
    expect($detalle->precio_unitario)->toBe('6.50');
});

test('la venta genera un correlativo con el formato VTA-AÑO-NNNNNN', function () {
    $this->actingAs($this->vendedor)->post(route('ventas.store'), [
        'metodo_pago' => MetodoPago::Plin->value,
        'lineas' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
    ]);

    $anio = now()->year;
    expect(Venta::first()->codigo)->toBe("VTA-{$anio}-000001");
});

test('los correlativos son consecutivos', function () {
    foreach (range(1, 3) as $i) {
        $this->actingAs($this->vendedor)->post(route('ventas.store'), [
            'metodo_pago' => MetodoPago::Efectivo->value,
            'lineas' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        ]);
    }

    $anio = now()->year;
    expect(Venta::orderBy('id')->pluck('codigo')->all())->toBe([
        "VTA-{$anio}-000001",
        "VTA-{$anio}-000002",
        "VTA-{$anio}-000003",
    ]);
});

test('la acción lanza excepción y revierte todo si falta stock', function () {
    $accion = new RegistrarVenta;

    expect(fn () => $accion->handle(
        $this->vendedor,
        [['producto_id' => $this->producto->id, 'cantidad' => 99]],
        MetodoPago::Efectivo,
    ))->toThrow(StockInsuficienteException::class);

    // La transacción hizo rollback: ni venta ni cambio de stock.
    $this->assertDatabaseCount('ventas', 0);
    $this->assertDatabaseCount('detalle_ventas', 0);
    expect($this->producto->fresh()->stock)->toBe(10);
});

test('agrupa cantidades del mismo producto repetido en varias líneas', function () {
    $accion = new RegistrarVenta;

    $venta = $accion->handle(
        $this->vendedor,
        [
            ['producto_id' => $this->producto->id, 'cantidad' => 2],
            ['producto_id' => $this->producto->id, 'cantidad' => 3],
        ],
        MetodoPago::Efectivo,
    );

    expect($venta->detalles)->toHaveCount(1)
        ->and($venta->detalles->first()->cantidad)->toBe(5)
        ->and($this->producto->fresh()->stock)->toBe(5);
});

test('un invitado no puede registrar ventas', function () {
    $this->post(route('ventas.store'), [
        'metodo_pago' => MetodoPago::Efectivo->value,
        'lineas' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
    ])->assertRedirect('/login');
});
