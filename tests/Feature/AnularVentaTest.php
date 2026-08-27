<?php

use App\Actions\AnularVenta;
use App\Actions\RegistrarVenta;
use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\Role;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => Role::Admin]);
    $this->vendedor = User::factory()->create(['role' => Role::Vendedor]);

    $categoria = Categoria::create(['nombre' => 'Bebidas']);
    $this->producto = Producto::create([
        'categoria_id' => $categoria->id,
        'nombre' => 'Coca-Cola 500ml',
        'precio' => 3.00,
        'stock' => 20,
        'stock_minimo' => 5,
    ]);

    // Venta de 4 unidades → stock queda en 16
    $this->venta = (new RegistrarVenta)->handle(
        $this->vendedor,
        [['producto_id' => $this->producto->id, 'cantidad' => 4]],
        MetodoPago::Efectivo,
    );
});

test('anular una venta reintegra el stock', function () {
    expect($this->producto->fresh()->stock)->toBe(16);

    $this->actingAs($this->admin)
        ->post(route('ventas.anular', $this->venta))
        ->assertRedirect(route('ventas.show', $this->venta));

    expect($this->producto->fresh()->stock)->toBe(20)
        ->and($this->venta->fresh()->estado)->toBe(EstadoVenta::Anulada)
        ->and($this->venta->fresh()->anulada_at)->not->toBeNull();
});

test('la venta anulada se conserva en el historial', function () {
    $this->actingAs($this->admin)->post(route('ventas.anular', $this->venta));

    // No se borra: sigue existiendo con sus detalles intactos.
    $this->assertDatabaseHas('ventas', ['id' => $this->venta->id]);
    $this->assertDatabaseCount('detalle_ventas', 1);
});

test('un vendedor no puede anular ventas', function () {
    $this->actingAs($this->vendedor)
        ->post(route('ventas.anular', $this->venta))
        ->assertForbidden();

    expect($this->producto->fresh()->stock)->toBe(16)
        ->and($this->venta->fresh()->estado)->toBe(EstadoVenta::Completada);
});

test('no se puede anular dos veces la misma venta', function () {
    $this->actingAs($this->admin)->post(route('ventas.anular', $this->venta));

    // El segundo intento es rechazado por la policy.
    $this->actingAs($this->admin)
        ->post(route('ventas.anular', $this->venta))
        ->assertForbidden();

    // El stock se devolvió una sola vez.
    expect($this->producto->fresh()->stock)->toBe(20);
});

test('anular es idempotente a nivel de acción', function () {
    $accion = new AnularVenta;

    $accion->handle($this->venta);
    $accion->handle($this->venta->fresh());

    expect($this->producto->fresh()->stock)->toBe(20);
});

test('el vendedor ve su propia venta pero no la de otro', function () {
    $otroVendedor = User::factory()->create(['role' => Role::Vendedor]);

    $this->actingAs($this->vendedor)
        ->get(route('ventas.show', $this->venta))
        ->assertOk();

    $this->actingAs($otroVendedor)
        ->get(route('ventas.show', $this->venta))
        ->assertForbidden();
});

test('el admin puede ver cualquier venta', function () {
    $this->actingAs($this->admin)
        ->get(route('ventas.show', $this->venta))
        ->assertOk()
        ->assertSee($this->venta->codigo);
});

test('el listado del vendedor solo incluye sus ventas', function () {
    $otroVendedor = User::factory()->create(['role' => Role::Vendedor]);
    $ventaAjena = (new RegistrarVenta)->handle(
        $otroVendedor,
        [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        MetodoPago::Yape,
    );

    $this->actingAs($this->vendedor)
        ->get(route('ventas.index'))
        ->assertOk()
        ->assertSee($this->venta->codigo)
        ->assertDontSee($ventaAjena->codigo);
});
