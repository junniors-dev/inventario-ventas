<?php

use App\Enums\Role;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;

function admin(): User
{
    return User::factory()->create(['role' => Role::Admin]);
}

function vendedor(): User
{
    return User::factory()->create(['role' => Role::Vendedor]);
}

test('el admin ve el listado de categorías', function () {
    $this->actingAs(admin())
        ->get(route('categorias.index'))
        ->assertOk();
});

test('un vendedor no puede acceder a categorías', function () {
    $this->actingAs(vendedor())
        ->get(route('categorias.index'))
        ->assertForbidden();
});

test('el admin puede crear una categoría', function () {
    $this->actingAs(admin())
        ->post(route('categorias.store'), [
            'nombre' => 'Abarrotes',
            'descripcion' => 'Productos secos',
        ])
        ->assertRedirect(route('categorias.index'));

    $this->assertDatabaseHas('categorias', ['nombre' => 'Abarrotes']);
});

test('el nombre de la categoría es obligatorio', function () {
    $this->actingAs(admin())
        ->post(route('categorias.store'), ['nombre' => ''])
        ->assertSessionHasErrors('nombre');

    $this->assertDatabaseCount('categorias', 0);
});

test('no permite nombres de categoría duplicados', function () {
    Categoria::create(['nombre' => 'Bebidas']);

    $this->actingAs(admin())
        ->post(route('categorias.store'), ['nombre' => 'Bebidas'])
        ->assertSessionHasErrors('nombre');

    $this->assertDatabaseCount('categorias', 1);
});

test('el admin puede actualizar una categoría', function () {
    $categoria = Categoria::create(['nombre' => 'Limpiza']);

    $this->actingAs(admin())
        ->patch(route('categorias.update', $categoria), ['nombre' => 'Limpieza'])
        ->assertRedirect(route('categorias.index'));

    $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'nombre' => 'Limpieza']);
});

test('no elimina una categoría que tiene productos', function () {
    $categoria = Categoria::create(['nombre' => 'Snacks']);
    Producto::create([
        'categoria_id' => $categoria->id,
        'nombre' => 'Galletas',
        'precio' => 1.50,
        'stock' => 10,
        'stock_minimo' => 5,
    ]);

    $this->actingAs(admin())
        ->delete(route('categorias.destroy', $categoria))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('categorias', ['id' => $categoria->id]);
});

test('no elimina una categoría cuyos productos fueron borrados lógicamente', function () {
    $categoria = Categoria::create(['nombre' => 'Descontinuados']);
    $producto = Producto::create([
        'categoria_id' => $categoria->id,
        'nombre' => 'Producto retirado',
        'precio' => 2.50,
        'stock' => 0,
        'stock_minimo' => 0,
    ]);
    $producto->delete();

    // La fila del producto sigue existiendo y ocupa la clave foránea, así que
    // borrar la categoría debe rechazarse con un mensaje, no con un error 500.
    $this->actingAs(admin())
        ->delete(route('categorias.destroy', $categoria))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('categorias', ['id' => $categoria->id]);
});

test('el admin puede eliminar una categoría vacía', function () {
    $categoria = Categoria::create(['nombre' => 'Temporal']);

    $this->actingAs(admin())
        ->delete(route('categorias.destroy', $categoria))
        ->assertRedirect(route('categorias.index'));

    $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
});
