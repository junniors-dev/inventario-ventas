<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Productos
            </h2>
            <a href="{{ route('productos.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                Nuevo producto
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            {{-- Filtros --}}
            <form method="GET" action="{{ route('productos.index') }}"
                  class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-48">
                    <label for="buscar" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Buscar</label>
                    <input id="buscar" name="buscar" type="search" value="{{ request('buscar') }}"
                           placeholder="Nombre o código de barras…" autocomplete="off"
                           class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                </div>
                <div class="min-w-44">
                    <label for="categoria_id" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Categoría</label>
                    <select id="categoria_id" name="categoria_id"
                            class="w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm text-sm">
                        <option value="">Todas</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}" @selected(request('categoria_id') == $categoria->id)>
                                {{ $categoria->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <label class="inline-flex items-center gap-2 pb-2 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="stock_bajo" value="1" @checked(request()->boolean('stock_bajo'))
                           class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-emerald-600 focus:ring-emerald-500">
                    Solo stock bajo
                </label>
                <button type="submit"
                        class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    Filtrar
                </button>
                @if (request()->hasAny(['buscar', 'categoria_id', 'stock_bajo']))
                    <a href="{{ route('productos.index') }}" class="text-sm text-gray-500 hover:text-gray-800 dark:hover:text-gray-200 pb-2">Limpiar</a>
                @endif
            </form>

            {{-- Tabla --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                @if ($productos->isEmpty())
                    <div class="p-10 text-center text-gray-500 dark:text-gray-400">
                        No se encontraron productos.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-6 py-3">Producto</th>
                                    <th class="px-6 py-3">Categoría</th>
                                    <th class="px-6 py-3 text-right">Precio</th>
                                    <th class="px-6 py-3 text-right">Stock</th>
                                    <th class="px-6 py-3 text-right">Mínimo</th>
                                    <th class="px-6 py-3">Estado</th>
                                    <th class="px-6 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($productos as $producto)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="px-6 py-4">
                                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ $producto->nombre }}</span>
                                            @if ($producto->codigo_barras)
                                                <span class="block font-mono text-xs text-gray-400">{{ $producto->codigo_barras }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                                                {{ $producto->categoria->nombre }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right tabular-nums text-gray-900 dark:text-gray-100">S/ {{ number_format($producto->precio, 2) }}</td>
                                        <td class="px-6 py-4 text-right tabular-nums {{ $producto->stock <= $producto->stock_minimo ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-700 dark:text-gray-300' }}">
                                            {{ $producto->stock }}
                                        </td>
                                        <td class="px-6 py-4 text-right tabular-nums text-gray-500 dark:text-gray-400">{{ $producto->stock_minimo }}</td>
                                        <td class="px-6 py-4"><x-stock-badge :producto="$producto" /></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('productos.edit', $producto) }}"
                                                   class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    Editar
                                                </a>
                                                <form method="POST" action="{{ route('productos.destroy', $producto) }}"
                                                      onsubmit="return confirm('¿Eliminar «{{ $producto->nombre }}»? El historial de ventas se conserva.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="inline-flex items-center rounded-md border border-red-200 dark:border-red-900 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4">
                        {{ $productos->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
