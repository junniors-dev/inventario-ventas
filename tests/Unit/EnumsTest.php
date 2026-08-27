<?php

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\Role;

test('los métodos de pago exponen sus valores para validación', function () {
    expect(MetodoPago::values())->toBe(['efectivo', 'yape', 'plin', 'transferencia']);
});

test('cada método de pago tiene una etiqueta legible', function () {
    foreach (MetodoPago::cases() as $metodo) {
        expect($metodo->label())->not->toBeEmpty();
    }

    expect(MetodoPago::Yape->label())->toBe('Yape')
        ->and(MetodoPago::Transferencia->label())->toBe('Transferencia');
});

test('los roles se resuelven desde su valor almacenado', function () {
    expect(Role::from('admin'))->toBe(Role::Admin)
        ->and(Role::from('vendedor'))->toBe(Role::Vendedor);
});

test('los roles tienen etiquetas en español', function () {
    expect(Role::Admin->label())->toBe('Administrador')
        ->and(Role::Vendedor->label())->toBe('Vendedor');
});

test('un valor de rol desconocido es rechazado', function () {
    expect(Role::tryFrom('superusuario'))->toBeNull();
});

test('los estados de venta cubren el ciclo completo', function () {
    expect(EstadoVenta::cases())->toHaveCount(2)
        ->and(EstadoVenta::Completada->label())->toBe('Completada')
        ->and(EstadoVenta::Anulada->label())->toBe('Anulada');
});
