<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Ventas
            </h2>
            <a href="{{ route('ventas.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                Nueva venta
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                @if ($ventas->isEmpty())
                    <div class="p-10 text-center text-gray-500 dark:text-gray-400">
                        Todavía no hay ventas registradas.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-6 py-3">Comprobante</th>
                                    <th class="px-6 py-3">Fecha</th>
                                    <th class="px-6 py-3">Vendedor</th>
                                    <th class="px-6 py-3">Pago</th>
                                    <th class="px-6 py-3 text-right">Ítems</th>
                                    <th class="px-6 py-3 text-right">Total</th>
                                    <th class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($ventas as $venta)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="px-6 py-4">
                                            <a href="{{ route('ventas.show', $venta) }}"
                                               class="font-semibold tabular-nums hover:underline {{ $venta->estado === \App\Enums\EstadoVenta::Anulada ? 'text-gray-400 line-through' : 'text-emerald-700 dark:text-emerald-400' }}">
                                                {{ $venta->codigo }}
                                            </a>
                                            @if ($venta->estado === \App\Enums\EstadoVenta::Anulada)
                                                <span class="ml-2 inline-flex rounded-full bg-red-50 dark:bg-red-950 px-2 py-0.5 text-[10px] font-bold uppercase text-red-700 dark:text-red-400">Anulada</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $venta->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-6 py-4 text-gray-900 dark:text-gray-100">{{ $venta->usuario->name }}</td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                                                {{ $venta->metodo_pago->label() }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right tabular-nums text-gray-700 dark:text-gray-300">{{ $venta->detalles_count }}</td>
                                        <td class="px-6 py-4 text-right font-semibold tabular-nums text-gray-900 dark:text-gray-100">S/ {{ number_format($venta->total, 2) }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('ventas.show', $venta) }}"
                                                   class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    Ver detalle
                                                </a>
                                                <a href="{{ route('ventas.ticket', $venta) }}" title="Descargar ticket PDF"
                                                   class="inline-flex items-center rounded-md border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                                                    <span class="sr-only">Descargar ticket</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-6 py-4">
                        {{ $ventas->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
