<?php

namespace App\Actions;

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
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
     *     ticketPromedio: float,
     *     serieMensual: Collection,
     *     masVendidos: Collection,
     *     porMetodoPago: Collection,
     *     rankingVendedores: Collection,
     *     productosStockBajo: Collection
     * }
     */
    public function handle(): array
    {
        $inicioMes = now()->startOfMonth();
        $inicioMesAnterior = now()->subMonthNoOverflow()->startOfMonth();

        $ventasDelMes = Venta::completadas()->where('created_at', '>=', $inicioMes);
        $totalMes = (float) (clone $ventasDelMes)->sum('total');
        $numeroVentasMes = (clone $ventasDelMes)->count();

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
            'ticketPromedio' => $numeroVentasMes > 0 ? round($totalMes / $numeroVentasMes, 2) : 0.0,
            'serieMensual' => $this->serieMensual(),
            'masVendidos' => $this->masVendidos(),
            'porMetodoPago' => $this->porMetodoPago($inicioMes),
            'rankingVendedores' => $this->rankingVendedores($inicioMes),
            'productosStockBajo' => Producto::with('categoria')->stockBajo()->orderBy('stock')->get(),
        ];
    }

    /**
     * Reparto de lo cobrado este mes entre los métodos de pago.
     */
    private function porMetodoPago(Carbon $desde): Collection
    {
        $totales = Venta::completadas()
            ->where('created_at', '>=', $desde)
            ->get(['metodo_pago', 'total'])
            ->groupBy(fn (Venta $venta) => $venta->metodo_pago->value)
            ->map(fn (Collection $ventas) => $ventas->sum(fn (Venta $v) => (float) $v->total));

        // Se listan todos los métodos, incluso los que no se usaron, para que
        // la gráfica no cambie de forma entre un mes y otro.
        return collect(MetodoPago::cases())->map(fn (MetodoPago $metodo) => [
            'etiqueta' => $metodo->label(),
            'total' => round((float) ($totales[$metodo->value] ?? 0), 2),
        ]);
    }

    /**
     * Vendedores ordenados por lo facturado este mes.
     */
    private function rankingVendedores(Carbon $desde): Collection
    {
        return Venta::completadas()
            ->with('usuario:id,name')
            ->where('created_at', '>=', $desde)
            ->get(['user_id', 'total'])
            ->groupBy('user_id')
            ->map(fn (Collection $ventas) => [
                'nombre' => $ventas->first()->usuario->name,
                'ventas' => $ventas->count(),
                'total' => round($ventas->sum(fn (Venta $v) => (float) $v->total), 2),
            ])
            ->sortByDesc('total')
            ->values();
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
