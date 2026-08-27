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
        'precio' => 1.50,
        'stock' => 500,
        'stock_minimo' => 20,
    ]);
});

test('la columna codigo tiene restricción única en la base de datos', function () {
    $venta = (new RegistrarVenta)->handle($this->vendedor,
        [['producto_id' => $this->producto->id, 'cantidad' => 1]], MetodoPago::Efectivo);

    // Insertar otro registro con el mismo código debe ser rechazado por la BD.
    expect(fn () => Venta::create([
        'codigo' => $venta->codigo,
        'user_id' => $this->vendedor->id,
        'total' => 10,
        'metodo_pago' => MetodoPago::Efectivo,
    ]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

test('la venta se reintenta cuando el correlativo ya fue tomado', function () {
    // Simula la carrera: otro proceso se adelanta y ocupa VTA-AÑO-000001
    // justo antes de que nuestra venta intente usarlo.
    $anio = now()->year;

    Venta::create([
        'codigo' => sprintf('VTA-%d-%06d', $anio, 1),
        'user_id' => $this->vendedor->id,
        'total' => 5,
        'metodo_pago' => MetodoPago::Efectivo,
    ]);

    $venta = (new RegistrarVenta)->handle($this->vendedor,
        [['producto_id' => $this->producto->id, 'cantidad' => 2]], MetodoPago::Yape);

    // La acción avanza al siguiente correlativo libre en lugar de fallar.
    expect($venta->codigo)->toBe(sprintf('VTA-%d-%06d', $anio, 2));
});

test('muchas ventas seguidas generan correlativos únicos', function () {
    foreach (range(1, 12) as $i) {
        (new RegistrarVenta)->handle($this->vendedor,
            [['producto_id' => $this->producto->id, 'cantidad' => 1]], MetodoPago::Efectivo);
    }

    expect(Venta::count())->toBe(12)
        ->and(Venta::distinct('codigo')->count('codigo'))->toBe(12);
});
