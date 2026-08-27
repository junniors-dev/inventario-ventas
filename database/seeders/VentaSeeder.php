<?php

namespace Database\Seeders;

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Enums\Role;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class VentaSeeder extends Seeder
{
    /**
     * Genera un histórico de ventas repartido en los últimos 8 meses.
     *
     * No usa la acción RegistrarVenta porque esta descuenta stock del
     * inventario actual: aquí interesa un histórico con fechas pasadas
     * que deje el stock del seeder intacto y con productos en alerta.
     */
    public function run(): void
    {
        $vendedores = User::whereIn('role', [Role::Vendedor, Role::Admin])->get();
        $productos = Producto::all();

        if ($vendedores->isEmpty() || $productos->isEmpty()) {
            return;
        }

        $correlativo = 1;
        $metodos = MetodoPago::cases();

        // Meses del 7 (más antiguo) al 0 (actual), con volumen creciente
        // para que la gráfica muestre una tendencia realista.
        foreach (range(7, 0) as $mesesAtras) {
            $mes = now()->subMonthsNoOverflow($mesesAtras);
            $ventasDelMes = random_int(14, 22) + (7 - $mesesAtras);

            foreach (range(1, $ventasDelMes) as $i) {
                $fecha = $this->fechaEnMes($mes);

                $venta = Venta::create([
                    'codigo' => sprintf('VTA-%d-%06d', $fecha->year, $correlativo++),
                    'user_id' => $vendedores->random()->id,
                    'total' => 0,
                    'metodo_pago' => $metodos[array_rand($metodos)],
                    // Alrededor del 4 % del histórico queda anulado.
                    'estado' => random_int(1, 25) === 1 ? EstadoVenta::Anulada : EstadoVenta::Completada,
                    'created_at' => $fecha,
                    'updated_at' => $fecha,
                ]);

                if ($venta->estado === EstadoVenta::Anulada) {
                    $venta->update(['anulada_at' => $fecha->copy()->addHours(2)]);
                }

                $total = 0.0;

                foreach ($productos->random(random_int(1, 5)) as $producto) {
                    $cantidad = random_int(1, 4);

                    $venta->detalles()->create([
                        'producto_id' => $producto->id,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $producto->precio,
                        'created_at' => $fecha,
                        'updated_at' => $fecha,
                    ]);

                    $total += $cantidad * (float) $producto->precio;
                }

                $venta->update(['total' => round($total, 2)]);
            }
        }
    }

    /**
     * Fecha aleatoria dentro del mes indicado, en horario comercial.
     */
    private function fechaEnMes(Carbon $mes): Carbon
    {
        $ultimoDia = $mes->isCurrentMonth() ? now()->day : $mes->daysInMonth;

        return $mes->copy()
            ->setDay(random_int(1, max(1, $ultimoDia)))
            ->setTime(random_int(8, 20), random_int(0, 59));
    }
}
