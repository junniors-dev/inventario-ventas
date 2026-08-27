<?php

use App\Actions\RegistrarVenta;
use App\Enums\MetodoPago;
use App\Enums\Role;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('un vendedor no puede acceder a la gestión de usuarios', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('usuarios.index'))
        ->assertForbidden();
});

test('el admin ve el listado de usuarios', function () {
    $vendedor = User::factory()->create(['name' => 'María Quispe']);

    $this->actingAs($this->admin)
        ->get(route('usuarios.index'))
        ->assertOk()
        ->assertSee('María Quispe');
});

test('el admin puede crear un usuario vendedor', function () {
    $this->actingAs($this->admin)
        ->post(route('usuarios.store'), [
            'name' => 'Carlos Ramos',
            'email' => 'carlos@bodega.pe',
            'role' => Role::Vendedor->value,
            'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123',
        ])
        ->assertRedirect(route('usuarios.index'));

    $usuario = User::where('email', 'carlos@bodega.pe')->first();

    expect($usuario)->not->toBeNull()
        ->and($usuario->isVendedor())->toBeTrue()
        ->and(Hash::check('clave-segura-123', $usuario->password))->toBeTrue();
});

test('el correo del usuario debe ser único', function () {
    User::factory()->create(['email' => 'repetido@bodega.pe']);

    $this->actingAs($this->admin)
        ->post(route('usuarios.store'), [
            'name' => 'Otro',
            'email' => 'repetido@bodega.pe',
            'role' => Role::Vendedor->value,
            'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123',
        ])
        ->assertSessionHasErrors('email');
});

test('la contraseña debe confirmarse al crear', function () {
    $this->actingAs($this->admin)
        ->post(route('usuarios.store'), [
            'name' => 'Nuevo',
            'email' => 'nuevo@bodega.pe',
            'role' => Role::Vendedor->value,
            'password' => 'clave-segura-123',
            'password_confirmation' => 'otra-distinta',
        ])
        ->assertSessionHasErrors('password');
});

test('editar sin escribir contraseña conserva la anterior', function () {
    $usuario = User::factory()->create(['name' => 'Antes']);
    $hashOriginal = $usuario->password;

    $this->actingAs($this->admin)
        ->patch(route('usuarios.update', $usuario), [
            'name' => 'Después',
            'email' => $usuario->email,
            'role' => Role::Vendedor->value,
            'password' => '',
        ])
        ->assertRedirect(route('usuarios.index'));

    expect($usuario->fresh()->name)->toBe('Después')
        ->and($usuario->fresh()->password)->toBe($hashOriginal);
});

test('el admin puede promover un vendedor a administrador', function () {
    $vendedor = User::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('usuarios.update', $vendedor), [
            'name' => $vendedor->name,
            'email' => $vendedor->email,
            'role' => Role::Admin->value,
        ]);

    expect($vendedor->fresh()->isAdmin())->toBeTrue();
});

test('un admin no puede quitarse a sí mismo el rol de administrador', function () {
    $this->actingAs($this->admin)
        ->patch(route('usuarios.update', $this->admin), [
            'name' => $this->admin->name,
            'email' => $this->admin->email,
            'role' => Role::Vendedor->value,
        ])
        ->assertSessionHas('error');

    expect($this->admin->fresh()->isAdmin())->toBeTrue();
});

test('un admin no puede eliminar su propia cuenta', function () {
    $this->actingAs($this->admin)
        ->delete(route('usuarios.destroy', $this->admin))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
});

test('no se puede eliminar un usuario con ventas registradas', function () {
    $vendedor = User::factory()->create();
    $categoria = Categoria::create(['nombre' => 'Bebidas']);
    $producto = Producto::create([
        'categoria_id' => $categoria->id,
        'nombre' => 'Agua',
        'precio' => 1.50,
        'stock' => 10,
        'stock_minimo' => 2,
    ]);

    (new RegistrarVenta)->handle($vendedor,
        [['producto_id' => $producto->id, 'cantidad' => 1]], MetodoPago::Efectivo);

    $this->actingAs($this->admin)
        ->delete(route('usuarios.destroy', $vendedor))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('users', ['id' => $vendedor->id]);
});

test('el admin puede eliminar un usuario sin ventas', function () {
    $vendedor = User::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('usuarios.destroy', $vendedor))
        ->assertRedirect(route('usuarios.index'));

    $this->assertDatabaseMissing('users', ['id' => $vendedor->id]);
});
