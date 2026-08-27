<?php

namespace App\Actions;

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Exceptions\StockInsuficienteException;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrarVenta
{
    /**
     * Reintentos ante colisión del correlativo de venta.
     */
    private const MAX_INTENTOS = 3;

    /**
     * Registra una venta descontando el stock de forma atómica.
     *
     * Todo ocurre dentro de una transacción con bloqueo pesimista
     * (lockForUpdate) sobre las filas de los productos implicados: si dos
     * cajeros venden el mismo producto a la vez, el segundo espera a que
     * el primero termine y vuelve a leer el stock real. Sin esto, ambos
     * podrían leer el mismo stock y venderlo dos veces.
     *
     * @param  array<int, array{producto_id: int, cantidad: int}>  $lineas
     * @param  array{nombre?: ?string, documento?: ?string}  $cliente
     *
     * @throws StockInsuficienteException
     */
    public function handle(User $usuario, array $lineas, MetodoPago $metodoPago, array $cliente = []): Venta
    {
        // El correlativo se deriva del último código existente, así que dos
        // ventas simultáneas pueden calcular el mismo número. El índice UNIQUE
        // de `codigo` lo impide a nivel de base de datos: si eso ocurre se
        // reintenta la transacción completa, que vuelve a leer el correlativo.
        $intentos = 0;

        while (true) {
            try {
                return $this->registrar($usuario, $lineas, $metodoPago, $cliente);
            } catch (UniqueConstraintViolationException $e) {
                if (++$intentos >= self::MAX_INTENTOS) {
                    throw $e;
                }
            }
        }
    }

    /**
     * @param  array<int, array{producto_id: int, cantidad: int}>  $lineas
     * @param  array{nombre?: ?string, documento?: ?string}  $cliente
     *
     * @throws StockInsuficienteException
     */
    private function registrar(User $usuario, array $lineas, MetodoPago $metodoPago, array $cliente): Venta
    {
        return DB::transaction(function () use ($usuario, $lineas, $metodoPago, $cliente): Venta {
            // Agrupar por producto: si la misma referencia llega dos veces,
            // se valida contra la cantidad total, no línea por línea.
            $cantidades = [];
            foreach ($lineas as $linea) {
                $id = (int) $linea['producto_id'];
                $cantidades[$id] = ($cantidades[$id] ?? 0) + (int) $linea['cantidad'];
            }

            // Bloquear las filas en un orden estable evita interbloqueos
            // cuando dos ventas simultáneas comparten productos.
            $productos = Producto::whereIn('id', array_keys($cantidades))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $venta = Venta::create([
                'codigo' => $this->siguienteCodigo(),
                'user_id' => $usuario->id,
                'cliente_nombre' => $cliente['nombre'] ?? null,
                'cliente_documento' => $cliente['documento'] ?? null,
                'total' => 0,
                'metodo_pago' => $metodoPago,
                'estado' => EstadoVenta::Completada,
            ]);

            $total = 0.0;

            foreach ($cantidades as $productoId => $cantidad) {
                $producto = $productos->get($productoId);

                if ($producto === null) {
                    throw new StockInsuficienteException('Uno de los productos ya no está disponible.');
                }

                if ($producto->stock < $cantidad) {
                    throw StockInsuficienteException::para($producto, $cantidad);
                }

                $venta->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $producto->precio,
                ]);

                $producto->decrement('stock', $cantidad);

                $total += $cantidad * (float) $producto->precio;
            }

            $venta->update(['total' => $total]);

            Log::info('Venta registrada', [
                'venta_id' => $venta->id,
                'codigo' => $venta->codigo,
                'user_id' => $usuario->id,
                'total' => $venta->total,
                'metodo_pago' => $metodoPago->value,
                'lineas' => count($cantidades),
            ]);

            return $venta;
        });
    }

    /**
     * Genera el siguiente correlativo del año: VTA-2026-000001.
     */
    private function siguienteCodigo(): string
    {
        $anio = now()->year;

        $ultimo = Venta::where('codigo', 'like', "VTA-{$anio}-%")
            ->orderByDesc('id')
            ->value('codigo');

        $siguiente = $ultimo === null
            ? 1
            : ((int) substr($ultimo, -6)) + 1;

        return sprintf('VTA-%d-%06d', $anio, $siguiente);
    }
}
