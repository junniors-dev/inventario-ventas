<?php

namespace App\Actions;

use App\Enums\EstadoVenta;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

class AnularVenta
{
    /**
     * Anula una venta y reintegra el stock de forma atómica.
     *
     * La venta no se borra: cambia de estado y queda registrada la fecha
     * de anulación, de modo que el historial contable se conserva íntegro.
     */
    public function handle(Venta $venta): Venta
    {
        return DB::transaction(function () use ($venta): Venta {
            // Recargar con bloqueo evita que dos anulaciones simultáneas
            // devuelvan el stock dos veces.
            $venta = Venta::whereKey($venta->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($venta->estado === EstadoVenta::Anulada) {
                return $venta;
            }

            $venta->load('detalles');

            $productos = Producto::whereIn('id', $venta->detalles->pluck('producto_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($venta->detalles as $detalle) {
                $productos->get($detalle->producto_id)?->increment('stock', $detalle->cantidad);
            }

            $venta->update([
                'estado' => EstadoVenta::Anulada,
                'anulada_at' => now(),
            ]);

            return $venta;
        });
    }
}
