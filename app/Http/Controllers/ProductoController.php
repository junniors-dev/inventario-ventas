<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductoRequest;
use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function index(Request $request): View
    {
        $productos = Producto::query()
            ->with('categoria')
            ->when($request->string('buscar')->trim()->value(), function ($query, string $buscar) {
                $query->where('nombre', 'like', "%{$buscar}%");
            })
            ->when($request->integer('categoria_id'), function ($query, int $categoriaId) {
                $query->where('categoria_id', $categoriaId);
            })
            ->when($request->boolean('stock_bajo'), fn ($query) => $query->stockBajo())
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        $categorias = Categoria::orderBy('nombre')->get();

        return view('productos.index', compact('productos', 'categorias'));
    }

    public function create(): View
    {
        $categorias = Categoria::orderBy('nombre')->get();

        return view('productos.create', compact('categorias'));
    }

    public function store(ProductoRequest $request): RedirectResponse
    {
        Producto::create($request->validated());

        return redirect()->route('productos.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Producto $producto): View
    {
        $categorias = Categoria::orderBy('nombre')->get();

        return view('productos.edit', compact('producto', 'categorias'));
    }

    public function update(ProductoRequest $request, Producto $producto): RedirectResponse
    {
        $producto->update($request->validated());

        return redirect()->route('productos.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        // Borrado lógico: el producto se oculta pero las ventas históricas se conservan.
        $producto->delete();

        return redirect()->route('productos.index')
            ->with('success', 'Producto eliminado. El historial de ventas se conserva.');
    }
}
