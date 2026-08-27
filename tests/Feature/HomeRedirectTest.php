<?php

use App\Models\User;

test('un invitado que entra a la raíz va al login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('un admin que entra a la raíz va al dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/')
        ->assertRedirect(route('dashboard'));
});

test('un vendedor que entra a la raíz va a registrar una venta', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('ventas.create'));
});
