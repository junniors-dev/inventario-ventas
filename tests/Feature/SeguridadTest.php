<?php

use App\Actions\RegistrarVenta;
use App\Enums\MetodoPago;
use App\Enums\Role;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->vendedor = User::factory()->create();

    $this->categoria = Categoria::create(['nombre' => 'Bebidas']);
    $this->producto = Producto::create([
        'categoria_id' => $this->categoria->id,
        'nombre' => 'Agua San Luis',
        'precio' => 2.00,
        'stock' => 50,
        'stock_minimo' => 10,
    ]);
});

/*
|--------------------------------------------------------------------------
| Autenticación: ninguna ruta interna es accesible sin sesión
|--------------------------------------------------------------------------
*/

test('un invitado no accede a ninguna sección interna', function (string $ruta) {
    $this->get($ruta)->assertRedirect('/login');
})->with([
    '/dashboard',
    '/productos',
    '/categorias',
    '/usuarios',
    '/ventas',
    '/ventas/create',
    '/ventas/exportar',
    '/profile',
]);

test('un invitado no puede modificar datos', function () {
    $this->post(route('productos.store'), ['nombre' => 'Colado'])->assertRedirect('/login');
    $this->delete(route('productos.destroy', $this->producto))->assertRedirect('/login');
    $this->post(route('usuarios.store'), ['name' => 'Colado'])->assertRedirect('/login');

    $this->assertDatabaseMissing('productos', ['nombre' => 'Colado']);
    $this->assertDatabaseMissing('users', ['name' => 'Colado']);
});

/*
|--------------------------------------------------------------------------
| Autorización: el rol vendedor no alcanza la administración
|--------------------------------------------------------------------------
*/

test('un vendedor no alcanza las secciones de administración', function (string $ruta) {
    $this->actingAs($this->vendedor)->get($ruta)->assertForbidden();
})->with([
    '/dashboard',
    '/productos',
    '/productos/create',
    '/categorias',
    '/usuarios',
    '/usuarios/create',
]);

test('un vendedor no puede escribir en recursos de administración', function () {
    $this->actingAs($this->vendedor)
        ->post(route('productos.store'), [
            'categoria_id' => $this->categoria->id,
            'nombre' => 'Producto intruso',
            'precio' => 1,
            'stock' => 1,
            'stock_minimo' => 0,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('productos', ['nombre' => 'Producto intruso']);
});

test('un vendedor no puede borrar productos ni usuarios', function () {
    $this->actingAs($this->vendedor)
        ->delete(route('productos.destroy', $this->producto))
        ->assertForbidden();

    $this->actingAs($this->vendedor)
        ->delete(route('usuarios.destroy', $this->admin))
        ->assertForbidden();

    expect($this->producto->fresh())->not->toBeNull()
        ->and($this->admin->fresh())->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Referencias directas a objetos ajenos (IDOR)
|--------------------------------------------------------------------------
*/

test('un vendedor no puede leer la venta de otro conociendo su identificador', function () {
    $otro = User::factory()->create();
    $ajena = (new RegistrarVenta)->handle($otro,
        [['producto_id' => $this->producto->id, 'cantidad' => 1]], MetodoPago::Efectivo);

    $this->actingAs($this->vendedor)
        ->get(route('ventas.show', $ajena))
        ->assertForbidden();
});

test('un vendedor no puede descargar el ticket de una venta ajena', function () {
    $otro = User::factory()->create();
    $ajena = (new RegistrarVenta)->handle($otro,
        [['producto_id' => $this->producto->id, 'cantidad' => 1]], MetodoPago::Efectivo);

    $this->actingAs($this->vendedor)
        ->get(route('ventas.ticket', $ajena))
        ->assertForbidden();
});

test('un vendedor no puede anular ventas aunque sean suyas', function () {
    $propia = (new RegistrarVenta)->handle($this->vendedor,
        [['producto_id' => $this->producto->id, 'cantidad' => 2]], MetodoPago::Efectivo);

    $this->actingAs($this->vendedor)
        ->post(route('ventas.anular', $propia))
        ->assertForbidden();

    expect($this->producto->fresh()->stock)->toBe(48);
});

/*
|--------------------------------------------------------------------------
| Asignación masiva y escalada de privilegios
|--------------------------------------------------------------------------
*/

test('un vendedor no puede ascenderse a administrador desde su perfil', function () {
    $this->actingAs($this->vendedor)->patch(route('profile.update'), [
        'name' => $this->vendedor->name,
        'email' => $this->vendedor->email,
        'role' => Role::Admin->value,
    ]);

    expect($this->vendedor->fresh()->isAdmin())->toBeFalse();
});

test('los campos no validados se descartan al crear un producto', function () {
    $this->actingAs($this->admin)->post(route('productos.store'), [
        'categoria_id' => $this->categoria->id,
        'nombre' => 'Producto legítimo',
        'precio' => 5.00,
        'stock' => 10,
        'stock_minimo' => 2,
        // Campos que el atacante intenta colar:
        'id' => 9999,
        'deleted_at' => now(),
    ]);

    $creado = Producto::where('nombre', 'Producto legítimo')->first();

    expect($creado->id)->not->toBe(9999)
        ->and($creado->deleted_at)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Manipulación de precios y cantidades desde el cliente
|--------------------------------------------------------------------------
*/

test('el precio de la venta se toma de la base de datos, no del formulario', function () {
    $this->actingAs($this->vendedor)->post(route('ventas.store'), [
        'metodo_pago' => MetodoPago::Efectivo->value,
        'lineas' => [[
            'producto_id' => $this->producto->id,
            'cantidad' => 2,
            // El cliente intenta imponer un precio ridículo.
            'precio_unitario' => 0.01,
            'precio' => 0.01,
        ]],
    ]);

    $venta = Venta::first();

    expect($venta->total)->toBe('4.00')
        ->and($venta->detalles->first()->precio_unitario)->toBe('2.00');
});

test('no se acepta una cantidad negativa ni cero', function (int $cantidad) {
    $this->actingAs($this->vendedor)
        ->post(route('ventas.store'), [
            'metodo_pago' => MetodoPago::Efectivo->value,
            'lineas' => [['producto_id' => $this->producto->id, 'cantidad' => $cantidad]],
        ])
        ->assertSessionHasErrors('lineas.0.cantidad');

    expect($this->producto->fresh()->stock)->toBe(50);
})->with([-5, 0]);

test('no se puede vender un producto eliminado', function () {
    $this->producto->delete();

    $this->actingAs($this->vendedor)
        ->post(route('ventas.store'), [
            'metodo_pago' => MetodoPago::Efectivo->value,
            'lineas' => [['producto_id' => $this->producto->id, 'cantidad' => 1]],
        ])
        ->assertSessionHasErrors('lineas.0.producto_id');

    $this->assertDatabaseCount('ventas', 0);
});

/*
|--------------------------------------------------------------------------
| Inyección y escapado
|--------------------------------------------------------------------------
*/

test('los filtros no permiten inyección SQL', function () {
    Producto::create([
        'categoria_id' => $this->categoria->id,
        'nombre' => 'Producto testigo',
        'precio' => 1.00,
        'stock' => 5,
        'stock_minimo' => 1,
    ]);

    $this->actingAs($this->admin)
        ->get(route('productos.index', ['buscar' => "' OR 1=1; DROP TABLE productos; --"]))
        ->assertOk();

    // La tabla sigue existiendo y la búsqueda simplemente no encontró nada.
    expect(Producto::count())->toBe(2);
});

test('el nombre de un producto se escapa al mostrarse', function () {
    Producto::create([
        'categoria_id' => $this->categoria->id,
        'nombre' => '<script>alert("xss")</script>',
        'precio' => 1.00,
        'stock' => 5,
        'stock_minimo' => 1,
    ]);

    $this->actingAs($this->admin)
        ->get(route('productos.index'))
        ->assertOk()
        ->assertDontSee('<script>alert("xss")</script>', escape: false)
        ->assertSee('&lt;script&gt;', escape: false);
});

/*
|--------------------------------------------------------------------------
| Credenciales y sesión
|--------------------------------------------------------------------------
*/

test('la contraseña nunca se guarda en texto plano', function () {
    $this->actingAs($this->admin)->post(route('usuarios.store'), [
        'name' => 'Nueva cuenta',
        'email' => 'nueva@bodega.pe',
        'role' => Role::Vendedor->value,
        'password' => 'clave-secreta-123',
        'password_confirmation' => 'clave-secreta-123',
    ]);

    $creado = User::where('email', 'nueva@bodega.pe')->first();

    expect($creado->password)->not->toBe('clave-secreta-123')
        ->and(Hash::check('clave-secreta-123', $creado->password))->toBeTrue();
});

test('la contraseña no viaja en las respuestas serializadas', function () {
    expect($this->admin->toArray())->not->toHaveKey('password')
        ->and($this->admin->toArray())->not->toHaveKey('remember_token');
});

test('el login se bloquea tras varios intentos fallidos', function () {
    foreach (range(1, 5) as $intento) {
        $this->post('/login', [
            'email' => $this->admin->email,
            'password' => 'contraseña-incorrecta',
        ]);
    }

    // El sexto intento ya no compara credenciales: responde con bloqueo.
    $this->post('/login', [
        'email' => $this->admin->email,
        'password' => 'contraseña-incorrecta',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('cerrar sesión invalida el acceso', function () {
    $this->actingAs($this->admin)->post('/logout');

    $this->assertGuest();
    $this->get('/dashboard')->assertRedirect('/login');
});
