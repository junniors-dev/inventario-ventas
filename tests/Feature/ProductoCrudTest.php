<?php

use App\Enums\Role;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => Role::Admin]);
    $this->categoria = Categoria::create(['nombre' => 'Abarrotes']);
});

function datosProducto(array $overrides = []): array
{
    return array_merge([
        'categoria_id' => test()->categoria->id,
        'nombre' => 'Arroz Costeño 1kg',
        'precio' => 4.50,
        'stock' => 20,
        'stock_minimo' => 10,
    ], $overrides);
}

test('un vendedor no puede acceder a productos', function () {
    $vendedor = User::factory()->create(['role' => Role::Vendedor]);

    $this->actingAs($vendedor)
        ->get(route('productos.index'))
        ->assertForbidden();
});

test('el admin puede crear un producto', function () {
    $this->actingAs($this->admin)
        ->post(route('productos.store'), datosProducto())
        ->assertRedirect(route('productos.index'));

    $this->assertDatabaseHas('productos', [
        'nombre' => 'Arroz Costeño 1kg',
        'precio' => '4.50',
    ]);
});

test('el precio debe ser mayor que cero', function () {
    $this->actingAs($this->admin)
        ->post(route('productos.store'), datosProducto(['precio' => 0]))
        ->assertSessionHasErrors('precio');

    $this->actingAs($this->admin)
        ->post(route('productos.store'), datosProducto(['precio' => -5]))
        ->assertSessionHasErrors('precio');

    $this->assertDatabaseCount('productos', 0);
});

test('el stock no puede ser negativo', function () {
    $this->actingAs($this->admin)
        ->post(route('productos.store'), datosProducto(['stock' => -1]))
        ->assertSessionHasErrors('stock');
});

test('la categoría es obligatoria y debe existir', function () {
    $this->actingAs($this->admin)
        ->post(route('productos.store'), datosProducto(['categoria_id' => 9999]))
        ->assertSessionHasErrors('categoria_id');
});

test('el admin puede actualizar un producto', function () {
    $producto = Producto::create(datosProducto());

    $this->actingAs($this->admin)
        ->patch(route('productos.update', $producto), datosProducto(['precio' => 5.90]))
        ->assertRedirect(route('productos.index'));

    expect($producto->fresh()->precio)->toBe('5.90');
});

test('eliminar un producto usa borrado lógico', function () {
    $producto = Producto::create(datosProducto());

    $this->actingAs($this->admin)
        ->delete(route('productos.destroy', $producto))
        ->assertRedirect(route('productos.index'));

    // Ya no aparece en consultas normales...
    $this->assertDatabaseMissing('productos', ['id' => $producto->id, 'deleted_at' => null]);
    // ...pero la fila sigue en la base de datos (historial intacto).
    expect(Producto::withTrashed()->find($producto->id))->not->toBeNull();
});

test('el filtro de stock bajo solo devuelve productos bajo el mínimo', function () {
    Producto::create(datosProducto(['nombre' => 'Con stock', 'stock' => 50, 'stock_minimo' => 10]));
    Producto::create(datosProducto(['nombre' => 'Escaso', 'stock' => 3, 'stock_minimo' => 10]));

    $this->actingAs($this->admin)
        ->get(route('productos.index', ['stock_bajo' => 1]))
        ->assertOk()
        ->assertSee('Escaso')
        ->assertDontSee('Con stock');
});
