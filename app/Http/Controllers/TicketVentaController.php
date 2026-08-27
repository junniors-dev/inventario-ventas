<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class TicketVentaController extends Controller
{
    /**
     * Descarga el comprobante de una venta en PDF.
     */
    public function show(Venta $venta): Response
    {
        Gate::authorize('view', $venta);

        $venta->load(['usuario', 'detalles.producto']);

        return Pdf::loadView('ventas.ticket', compact('venta'))
            ->setPaper('a5')
            ->download("{$venta->codigo}.pdf");
    }
}
