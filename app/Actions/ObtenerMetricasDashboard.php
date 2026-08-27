<?php

namespace App\Actions;

use App\Enums\EstadoVenta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ObtenerMetricasDashboard
{
    /**
     * Reúne todas las métricas del panel.
     *
     * @return array{
     *     ventasMes: float,
     *     variacionMes: float|null,
     *     ventasHoy: int,
     *     totalHoy: float,
     *     productosActivos: int,
     *     stockBajo: int,
     *     serieMensual: Collection,
     *     masVendidos: Collection,
     *     productosStockBajo: Collection
     * }
     */
    public function handle(): array
    {
        $inicioMes = now()->startOfMonth();
        $inicioMesAnterior = now()->subMonthNoOverflow()->startOfMonth();

        $totalMes = (float) Venta::completadas()
            ->where('created_at', '>=', $inicioMes)
            ->sum('total');

        $totalMesAnterior = (float) Venta::completadas()
            ->whereBetween('created_at', [$inicioMesAnterior, $inicioMes])
            ->sum('total');

        $ventasDeHoy = Venta::completadas()->where('created_at', '>=', now()->startOfDay());

        return [
            'ventasMes' => $totalMes,
            'variacionMes' => $totalMesAnterior > 0
                ? round((($totalMes - $totalMesAnterior) / $totalMesAnterior) * 100, 1)
                : null,
            'ventasHoy' => (clone $ventasDeHoy)->count(),
            'totalHoy' => (float) (clone $ventasDeHoy)->sum('total'),
            'productosActivos' => Producto::count(),
            'stockBajo' => Producto::stockBajo()->count(),
            'serieMensual' => $this->serieMensual(),
            'masVendidos' => $this->masVendidos(),
            'productosStockBajo' => Producto::with('categoria')->stockBajo()->orderBy('stock')->get(),
        ];
    }

    /**
     * Total vendido en cada uno de los últimos 8 meses.
     */
    private function serieMensual(): Collection
    {
        $desde = now()->subMonthsNoOverflow(7)->startOfMonth();

        // Se agrupa en PHP en lugar de con funciones de fecha del motor
        // (DATE_FORMAT es exclusivo de MySQL): así la consulta funciona igual
        // en MySQL, SQLite y PostgreSQL. El rango son 8 meses, no hay
        // problema de volumen.
        $porMes = Venta::query()
            ->completadas()
            ->where('created_at', '>=', $desde)
            ->get(['created_at', 'total'])
            ->groupBy(fn (Venta $venta) => $venta->created_at->format('Y-m'))
            ->map(fn (Collection $ventas) => $ventas->sum(fn (Venta $v) => (float) $v->total));

        // Rellenar los meses sin ventas para que la gráfica no tenga huecos.
        return collect(range(7, 0))->map(function (int $atras) use ($porMes) {
            $mes = now()->subMonthsNoOverflow($atras)->startOfMonth();

            return [
                'etiqueta' => Carbon::parse($mes)->translatedFormat('M'),
                'total' => round((float) ($porMes[$mes->format('Y-m')] ?? 0), 2),
            ];
        });
    }

    /**
     * Los cinco productos con más unidades vendidas.
     */
    private function masVendidos(): Collection
    {
        return DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_ventas.venta_id')
            ->join('productos', 'productos.id', '=', 'detalle_ventas.producto_id')
            ->where('ventas.estado', EstadoVenta::Completada->value)
            ->groupBy('productos.id', 'productos.nombre')
            ->orderByDesc('unidades')
            ->limit(5)
            ->get([
                'productos.nombre',
                DB::raw('SUM(detalle_ventas.cantidad) AS unidades'),
            ]);
    }
}
