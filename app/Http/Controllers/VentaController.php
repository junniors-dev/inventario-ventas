<?php

namespace App\Http\Controllers;

use App\Actions\RegistrarVenta;
use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use App\Exceptions\StockInsuficienteException;
use App\Http\Requests\VentaRequest;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Support\FiltrosVenta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VentaController extends Controller
{
    public function index(Request $request): View
    {
        $consulta = Venta::query()
            ->with('usuario')
            ->withCount('detalles')
            // El vendedor solo ve sus propias ventas; el admin las ve todas.
            ->unless($request->user()->isAdmin(), fn ($query) => $query->whereBelongsTo($request->user(), 'usuario'))
            ->filtradas(FiltrosVenta::desdePeticion($request));

        // Totales del conjunto filtrado, no solo de la página visible.
        $resumen = [
            'ventas' => (clone $consulta)->completadas()->count(),
            'total' => (float) (clone $consulta)->completadas()->sum('total'),
        ];

        $ventas = $consulta
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('ventas.index', [
            'ventas' => $ventas,
            'resumen' => $resumen,
            'metodosPago' => MetodoPago::cases(),
            'estados' => EstadoVenta::cases(),
            // Solo el admin puede filtrar por vendedor: los demás ya ven únicamente lo suyo.
            'vendedores' => $request->user()->isAdmin()
                ? User::orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    /**
     * Productos que se envían a la pantalla de venta.
     *
     * El buscador del punto de venta filtra en el navegador, así que el
     * catálogo viaja completo. Se acota para que un inventario grande no
     * infle la respuesta; si se supera el límite, la vista avisa de que
     * conviene usar el buscador.
     */
    private const MAX_PRODUCTOS_POS = 250;

    public function create(): View
    {
        // Se incluyen los productos agotados: al escanear uno, el cajero debe
        // leer «sin stock» y no «código desconocido». En la grilla aparecen
        // deshabilitados y ordenados después de los disponibles.
        $productos = Producto::query()
            ->with('categoria')
            ->orderByRaw('stock = 0')
            ->orderBy('nombre')
            ->limit(self::MAX_PRODUCTOS_POS)
            ->get();

        $catalogoTruncado = Producto::count() > self::MAX_PRODUCTOS_POS;

        $categorias = Categoria::orderBy('nombre')->get();

        return view('ventas.create', [
            'productos' => $productos,
            'categorias' => $categorias,
            'metodosPago' => MetodoPago::cases(),
            'catalogoTruncado' => $catalogoTruncado,
        ]);
    }

    public function store(VentaRequest $request, RegistrarVenta $registrar): RedirectResponse
    {
        try {
            $venta = $registrar->handle(
                $request->user(),
                $request->validated('lineas'),
                MetodoPago::from($request->validated('metodo_pago')),
                [
                    'nombre' => $request->validated('cliente_nombre'),
                    'documento' => $request->validated('cliente_documento'),
                ],
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
