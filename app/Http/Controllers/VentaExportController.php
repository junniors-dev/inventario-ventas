<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Support\FiltrosVenta;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VentaExportController extends Controller
{
    /**
     * Columnas del archivo exportado.
     */
    private const CABECERAS = [
        'Comprobante', 'Fecha', 'Hora', 'Cliente', 'Documento',
        'Vendedor', 'Metodo de pago', 'Estado', 'Items', 'Total',
    ];

    /**
     * Descarga el historial filtrado en CSV.
     *
     * La respuesta se emite por streaming y la consulta se recorre con
     * cursor(), de modo que exportar miles de ventas no carga el histórico
     * completo en memoria.
     */
    public function show(Request $request): StreamedResponse
    {
        $ventas = Venta::query()
            ->with('usuario:id,name')
            ->withCount('detalles')
            // Un vendedor solo puede exportar sus propias ventas.
            ->unless($request->user()->isAdmin(), fn ($query) => $query->whereBelongsTo($request->user(), 'usuario'))
            ->filtradas(FiltrosVenta::desdePeticion($request))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $nombre = 'ventas-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($ventas) {
            $salida = fopen('php://output', 'w');

            // Excel en español necesita el BOM para leer las tildes y el
            // punto y coma como separador de columnas.
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, self::CABECERAS, ';');

            foreach ($ventas->cursor() as $venta) {
                fputcsv($salida, [
                    $venta->codigo,
                    $venta->created_at->format('d/m/Y'),
                    $venta->created_at->format('H:i'),
                    $venta->cliente_nombre ?? 'Publico general',
                    $venta->cliente_documento ?? '',
                    $venta->usuario->name,
                    $venta->metodo_pago->label(),
                    $venta->estado->label(),
                    $venta->detalles_count,
                    number_format((float) $venta->total, 2, ',', ''),
                ], ';');
            }

            fclose($salida);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
