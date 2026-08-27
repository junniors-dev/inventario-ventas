<?php

use App\Actions\RegistrarVenta;
use App\Enums\MetodoPago;
use App\Enums\Role;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->vendedor = User::factory()->create();

    $categoria = Categoria::create(['nombre' => 'Bebidas']);
    $producto = Producto::create([
        'categoria_id' => $categoria->id,
        'nombre' => 'Inca Kola 1.5L',
        'precio' => 6.50,
        'stock' => 20,
        'stock_minimo' => 5,
    ]);

    $this->venta = (new RegistrarVenta)->handle(
        $this->vendedor,
        [['producto_id' => $producto->id, 'cantidad' => 2]],
        MetodoPago::Yape,
    );
});

test('el ticket se descarga como PDF', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('ventas.ticket', $this->venta));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertDownload("{$this->venta->codigo}.pdf");
});

test('el PDF generado es un archivo válido', function () {
    $contenido = $this->actingAs($this->admin)
        ->get(route('ventas.ticket', $this->venta))
        ->getContent();

    // Todo PDF empieza con la firma %PDF
    expect($contenido)->toStartWith('%PDF')
        ->and(strlen($contenido))->toBeGreaterThan(1000);
});

test('el vendedor puede descargar el ticket de su propia venta', function () {
    $this->actingAs($this->vendedor)
        ->get(route('ventas.ticket', $this->venta))
        ->assertOk();
});

test('un vendedor no puede descargar el ticket de otro vendedor', function () {
    $otro = User::factory()->create(['role' => Role::Vendedor]);

    $this->actingAs($otro)
        ->get(route('ventas.ticket', $this->venta))
        ->assertForbidden();
});

test('un invitado no puede descargar tickets', function () {
    $this->get(route('ventas.ticket', $this->venta))
        ->assertRedirect('/login');
});
