<?php

namespace App\Http\Controllers;

use App\Actions\AnularVenta;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class VentaAnuladaController extends Controller
{
    /**
     * Anula una venta y reintegra su stock.
     */
    public function store(Venta $venta, AnularVenta $anular): RedirectResponse
    {
        Gate::authorize('anular', $venta);

        $anular->handle($venta);

        return redirect()->route('ventas.show', $venta)
            ->with('success', "Venta {$venta->codigo} anulada. El stock fue reintegrado.");
    }
}
