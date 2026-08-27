<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Venta {{ $venta->codigo }}
            </h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('ventas.ticket', $venta) }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                    Descargar ticket
                </a>
                <a href="{{ route('ventas.index') }}" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                    ← Volver
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 border-b border-gray-200 dark:border-gray-700 p-6">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Fecha</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $venta->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Vendedor</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $venta->usuario->name }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Método de pago</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">{{ $venta->metodo_pago->label() }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-gray-500">Estado</p>
                        <p class="mt-1">
                            @if ($venta->estado === \App\Enums\EstadoVenta::Anulada)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 dark:bg-red-950 px-2.5 py-1 text-xs font-semibold text-red-700 dark:text-red-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>Anulada
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>Completada
                                </span>
                            @endif
                        </p>
                    </div>
                </div>

                @if ($venta->cliente_nombre || $venta->cliente_documento)
                    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                        <p class="text-xs uppercase tracking-wide text-gray-500">Cliente</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $venta->cliente_nombre ?: 'Sin nombre' }}
                            @if ($venta->cliente_documento)
                                <span class="ml-2 font-mono text-xs font-normal text-gray-500">{{ $venta->cliente_documento }}</span>
                            @endif
                        </p>
                    </div>
                @endif

                @if ($venta->estado === \App\Enums\EstadoVenta::Anulada)
                    <div class="border-b border-gray-200 dark:border-gray-700 bg-red-50 dark:bg-red-950/40 px-6 py-3">
                        <p class="text-sm text-red-700 dark:text-red-400">
                            Esta venta fue anulada el {{ $venta->anulada_at?->format('d/m/Y H:i') }} y su stock fue reintegrado.
                        </p>
                    </div>
                @endif

                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left">Producto</th>
                            <th class="px-6 py-3 text-right">Cantidad</th>
                            <th class="px-6 py-3 text-right">P. unitario</th>
                            <th class="px-6 py-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($venta->detalles as $detalle)
                            <tr>
                                <td class="px-6 py-4 text-gray-900 dark:text-gray-100">{{ $detalle->producto->nombre }}</td>
                                <td class="px-6 py-4 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $detalle->cantidad }}</td>
                                <td class="px-6 py-4 text-right tabular-nums text-gray-700 dark:text-gray-300">S/ {{ number_format($detalle->precio_unitario, 2) }}</td>
                                <td class="px-6 py-4 text-right tabular-nums font-medium text-gray-900 dark:text-gray-100">S/ {{ number_format($detalle->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-gray-300 dark:border-gray-600">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-gray-100">Total</td>
                            <td class="px-6 py-4 text-right text-xl font-bold tabular-nums text-gray-900 dark:text-gray-100">S/ {{ number_format($venta->total, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>

                @can('anular', $venta)
                    <div class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 px-6 py-4 flex items-center justify-between gap-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Anular devuelve el stock al inventario. La venta se conserva en el historial.
                        </p>
                        <form method="POST" action="{{ route('ventas.anular', $venta) }}"
                              onsubmit="return confirm('¿Anular la venta {{ $venta->codigo }}? El stock será reintegrado.');">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center whitespace-nowrap rounded-lg border border-red-300 dark:border-red-900 px-4 py-2 text-sm font-semibold text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950">
                                Anular venta
                            </button>
                        </form>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
