<?php

use App\Actions\AnularVenta;
use App\Actions\RegistrarVenta;
use App\Enums\MetodoPago;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Junni Díaz']);
    $this->maria = User::factory()->create(['name' => 'María Quispe']);

    $categoria = Categoria::create(['nombre' => 'Bebidas']);
    $this->producto = Producto::create([
        'categoria_id' => $categoria->id,
        'nombre' => 'Agua San Luis',
        'precio' => 2.00,
        'stock' => 500,
        'stock_minimo' => 20,
    ]);
});

function registrar(User $usuario, MetodoPago $metodo = MetodoPago::Efectivo, int $cantidad = 1)
{
    return (new RegistrarVenta)->handle($usuario,
        [['producto_id' => test()->producto->id, 'cantidad' => $cantidad]], $metodo);
}

function csvDe($respuesta): string
{
    ob_start();
    $respuesta->sendContent();

    return ob_get_clean();
}

test('el archivo se descarga como CSV', function () {
    registrar($this->maria);

    $respuesta = $this->actingAs($this->admin)->get(route('ventas.exportar'));

    $respuesta->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($respuesta->headers->get('content-disposition'))->toContain('.csv');
});

test('el CSV incluye la cabecera y las ventas', function () {
    $venta = registrar($this->maria, MetodoPago::Yape, 3);

    $csv = csvDe($this->actingAs($this->admin)->get(route('ventas.exportar')));

    expect($csv)->toContain('Comprobante;Fecha;Hora;Vendedor')
        ->and($csv)->toContain($venta->codigo)
        ->and($csv)->toContain('María Quispe')
        ->and($csv)->toContain('Yape')
        ->and($csv)->toContain('6,00');
});

test('el CSV empieza con el BOM para que Excel lea las tildes', function () {
    registrar($this->maria);

    $csv = csvDe($this->actingAs($this->admin)->get(route('ventas.exportar')));

    expect($csv)->toStartWith("\xEF\xBB\xBF");
});

test('la exportación respeta los filtros aplicados', function () {
    $enYape = registrar($this->maria, MetodoPago::Yape);
    $enEfectivo = registrar($this->maria, MetodoPago::Efectivo);

    $csv = csvDe($this->actingAs($this->admin)->get(route('ventas.exportar', ['metodo_pago' => 'yape'])));

    expect($csv)->toContain($enYape->codigo)
        ->and($csv)->not->toContain($enEfectivo->codigo);
});

test('las ventas anuladas se exportan marcadas como tales', function () {
    $venta = registrar($this->maria);
    (new AnularVenta)->handle($venta);

    $csv = csvDe($this->actingAs($this->admin)->get(route('ventas.exportar')));

    expect($csv)->toContain($venta->codigo)
        ->and($csv)->toContain('Anulada');
});

test('un vendedor solo exporta sus propias ventas', function () {
    $suya = registrar($this->maria);
    $ajena = registrar($this->admin);

    $csv = csvDe($this->actingAs($this->maria)->get(route('ventas.exportar')));

    expect($csv)->toContain($suya->codigo)
        ->and($csv)->not->toContain($ajena->codigo);
});

test('un invitado no puede exportar', function () {
    $this->get(route('ventas.exportar'))->assertRedirect('/login');
});

test('la ruta de exportar no colisiona con el detalle de una venta', function () {
    registrar($this->maria);

    // /ventas/exportar debe llegar al exportador, no interpretarse
    // como el identificador de una venta.
    $this->actingAs($this->admin)
        ->get(route('ventas.exportar'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});
