<?php

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->categoria = Categoria::create(['nombre' => 'Bebidas']);
});

function producto(array $overrides = []): array
{
    return array_merge([
        'categoria_id' => test()->categoria->id,
        'nombre' => 'Inca Kola 1.5L',
        'codigo_barras' => '7751234567890',
        'precio' => 6.50,
        'stock' => 20,
        'stock_minimo' => 5,
    ], $overrides);
}

test('el admin puede registrar un producto con código de barras', function () {
    $this->actingAs($this->admin)
        ->post(route('productos.store'), producto())
        ->assertRedirect(route('productos.index'));

    $this->assertDatabaseHas('productos', ['codigo_barras' => '7751234567890']);
});

test('el código de barras es opcional', function () {
    $this->actingAs($this->admin)
        ->post(route('productos.store'), producto(['codigo_barras' => null]))
        ->assertRedirect(route('productos.index'));

    $this->assertDatabaseHas('productos', ['nombre' => 'Inca Kola 1.5L', 'codigo_barras' => null]);
});

test('no se puede repetir un código de barras entre productos', function () {
    Producto::create(producto());

    $this->actingAs($this->admin)
        ->post(route('productos.store'), producto(['nombre' => 'Otro producto']))
        ->assertSessionHasErrors('codigo_barras');

    $this->assertDatabaseCount('productos', 1);
});

test('varios productos pueden quedarse sin código de barras', function () {
    Producto::create(producto(['nombre' => 'A granel 1', 'codigo_barras' => null]));

    $this->actingAs($this->admin)
        ->post(route('productos.store'), producto(['nombre' => 'A granel 2', 'codigo_barras' => null]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseCount('productos', 2);
});

test('editar un producto conserva su propio código de barras', function () {
    $existente = Producto::create(producto());

    $this->actingAs($this->admin)
        ->patch(route('productos.update', $existente), producto(['precio' => 7.50]))
        ->assertSessionDoesntHaveErrors();

    expect($existente->fresh()->precio)->toBe('7.50');
});

test('el listado de productos permite buscar por código de barras', function () {
    Producto::create(producto());
    Producto::create(producto(['nombre' => 'Agua San Luis', 'codigo_barras' => '7759999999999']));

    $this->actingAs($this->admin)
        ->get(route('productos.index', ['buscar' => '7759999999999']))
        ->assertOk()
        ->assertSee('Agua San Luis')
        ->assertDontSee('Inca Kola 1.5L');
});

test('la pantalla de venta expone el código de barras para el lector', function () {
    Producto::create(producto());

    $this->actingAs($this->admin)
        ->get(route('ventas.create'))
        ->assertOk()
        ->assertSee('7751234567890')
        ->assertSee('Escanea un código');
});

test('el seeder genera códigos EAN-13 válidos', function () {
    $this->seed(Database\Seeders\CategoriaSeeder::class);
    $this->seed(Database\Seeders\ProductoSeeder::class);

    $conCodigo = Producto::whereNotNull('codigo_barras')->get();

    expect($conCodigo)->not->toBeEmpty();

    foreach ($conCodigo as $item) {
        expect($item->codigo_barras)->toHaveLength(13);

        // Verificar el dígito de control del EAN-13.
        $digitos = str_split($item->codigo_barras);
        $control = (int) array_pop($digitos);

        $suma = 0;
        foreach ($digitos as $posicion => $digito) {
            $suma += (int) $digito * ($posicion % 2 === 0 ? 1 : 3);
        }

        expect((10 - $suma % 10) % 10)->toBe($control);
    }
});
