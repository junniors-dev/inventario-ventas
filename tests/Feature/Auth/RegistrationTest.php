<?php

use App\Models\User;

test('el registro público está deshabilitado', function () {
    // Las cuentas las crea un administrador desde /usuarios; nadie ajeno
    // al negocio debe poder darse de alta y ver el inventario.
    $this->get('/register')->assertNotFound();
});

test('no se puede crear una cuenta enviando el formulario de registro', function () {
    $this->post('/register', [
        'name' => 'Intruso',
        'email' => 'intruso@ejemplo.com',
        'password' => 'clave-segura-123',
        'password_confirmation' => 'clave-segura-123',
    ])->assertNotFound();

    expect(User::where('email', 'intruso@ejemplo.com')->exists())->toBeFalse();
});
