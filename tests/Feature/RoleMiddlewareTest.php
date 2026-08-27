<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'role:admin'])
        ->get('/_test-solo-admin', fn () => 'contenido admin');
});

test('un invitado es redirigido al login', function () {
    $this->get('/_test-solo-admin')->assertRedirect('/login');
});

test('un vendedor recibe 403 en una ruta solo-admin', function () {
    $vendedor = User::factory()->create(['role' => Role::Vendedor]);

    $this->actingAs($vendedor)
        ->get('/_test-solo-admin')
        ->assertForbidden();
});

test('un admin accede a una ruta solo-admin', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get('/_test-solo-admin')
        ->assertOk()
        ->assertSee('contenido admin');
});
