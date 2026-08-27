<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Cuentas de demostración con credenciales conocidas.
        User::updateOrCreate(
            ['email' => 'admin@bodega.pe'],
            [
                'name' => 'Junni Díaz',
                'password' => 'password',
                'role' => Role::Admin,
                'email_verified_at' => now(),
            ],
        );

        collect([
            ['María Quispe', 'maria@bodega.pe'],
            ['Carlos Ramos', 'carlos@bodega.pe'],
        ])->each(fn (array $datos) => User::updateOrCreate(
            ['email' => $datos[1]],
            [
                'name' => $datos[0],
                'password' => 'password',
                'role' => Role::Vendedor,
                'email_verified_at' => now(),
            ],
        ));
    }
}
