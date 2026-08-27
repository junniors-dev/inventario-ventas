<?php

namespace App\Http\Controllers;

use App\Actions\RegistrarVenta;
use App\Enums\MetodoPago;
use App\Exceptions\StockInsuficienteException;
use App\Http\Requests\VentaRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function index(Request $request): View
    {
        $ventas = Venta::query()
            ->with('usuario')
            ->withCount('detalles')
            // El vendedor solo ve sus propias ventas; el admin las ve todas.
            ->unless($request->user()->isAdmin(), fn ($query) => $query->whereBelongsTo($request->user(), 'usuario'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('ventas.index', compact('ventas'));
    }

    public function create(): View
    {
        $productos = Producto::query()
            ->with('categoria')
            ->where('stock', '>', 0)
            ->orderBy('nombre')
            ->get();

        $categorias = Categoria::orderBy('nombre')->get();

        return view('ventas.create', [
            'productos' => $productos,
            'categorias' => $categorias,
            'metodosPago' => MetodoPago::cases(),
        ]);
    }

    public function store(VentaRequest $request, RegistrarVenta $registrar): RedirectResponse
    {
        try {
            $venta = $registrar->handle(
                $request->user(),
                $request->validated('lineas'),
                MetodoPago::from($request->validated('metodo_pago')),
            );
        } catch (StockInsuficienteException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('ventas.show', $venta)
            ->with('success', "Venta {$venta->codigo} registrada correctamente.");
    }

    public function show(Venta $venta): View
    {
        Gate::authorize('view', $venta);

        $venta->load(['usuario', 'detalles.producto']);

        return view('ventas.show', compact('venta'));
    }
}
