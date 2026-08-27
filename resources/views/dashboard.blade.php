<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Dashboard</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Resumen de {{ now()->translatedFormat('F Y') }}</p>
            </div>
            <a href="{{ route('ventas.create') }}"
               class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></svg>
                Registrar venta
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">

            {{-- KPIs --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                <x-kpi-card label="Ventas del mes" :value="'S/ '.number_format($ventasMes, 2)" tone="accent">
                    <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></x-slot>
                    <x-slot name="footnote">
                        @if ($variacionMes === null)
                            <span class="text-gray-500">Sin datos del mes anterior</span>
                        @elseif ($variacionMes >= 0)
                            <span class="text-emerald-600 dark:text-emerald-400">▲ {{ number_format($variacionMes, 1) }}% vs mes anterior</span>
                        @else
                            <span class="text-red-600 dark:text-red-400">▼ {{ number_format(abs($variacionMes), 1) }}% vs mes anterior</span>
                        @endif
                    </x-slot>
                </x-kpi-card>

                <x-kpi-card label="Productos activos" :value="$productosActivos" tone="accent">
                    <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7 12 3 4 7l8 4 8-4Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10l8 4 8-4V7"/></x-slot>
                    <x-slot name="footnote"><span class="text-gray-500">en el catálogo</span></x-slot>
                </x-kpi-card>

                <x-kpi-card label="Stock bajo" :value="$stockBajo" tone="warn">
                    <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4M12 17h.01"/></x-slot>
                    <x-slot name="footnote">
                        <span class="{{ $stockBajo > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500' }}">
                            {{ $stockBajo > 0 ? 'requieren reposición' : 'todo en orden' }}
                        </span>
                    </x-slot>
                </x-kpi-card>

                <x-kpi-card label="Ventas de hoy" :value="$ventasHoy" tone="accent">
                    <x-slot name="icon"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></x-slot>
                    <x-slot name="footnote"><span class="text-gray-500">S/ {{ number_format($totalHoy, 2) }} acumulado</span></x-slot>
                </x-kpi-card>

                <x-kpi-card label="Ticket promedio" :value="'S/ '.number_format($ticketPromedio, 2)" tone="accent">
                    <x-slot name="icon"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6M9 11h6M9 15h3"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 2h12a1 1 0 0 1 1 1v18l-3-2-2 2-2-2-2 2-2-2-3 2V3a1 1 0 0 1 1-1Z"/></x-slot>
                    <x-slot name="footnote"><span class="text-gray-500">por venta este mes</span></x-slot>
                </x-kpi-card>
            </div>

            {{-- Gráficas --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2 bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Ventas por mes</h3>
                        <span class="text-xs text-gray-500">últimos 8 meses · S/</span>
                    </div>
                    <div class="h-64">
                        <canvas id="graficaVentas" role="img"
                                aria-label="Gráfico de barras con el total vendido en cada uno de los últimos ocho meses"></canvas>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Más vendidos</h3>
                        <span class="text-xs text-gray-500">unidades</span>
                    </div>
                    @if ($masVendidos->isEmpty())
                        <p class="py-10 text-center text-sm text-gray-400">Aún no hay ventas registradas.</p>
                    @else
                        <div class="h-64">
                            <canvas id="graficaTop" role="img"
                                    aria-label="Gráfico de barras horizontales con los cinco productos más vendidos"></canvas>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Cobros por método de pago y ranking de vendedores --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Cobros por método de pago</h3>
                        <span class="text-xs text-gray-500">este mes</span>
                    </div>
                    <div class="h-56">
                        <canvas id="graficaPagos" role="img"
                                aria-label="Gráfico de dona con el reparto de lo cobrado este mes entre efectivo, Yape, Plin y transferencia"></canvas>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Ranking de vendedores</h3>
                        <span class="text-xs text-gray-500">este mes</span>
                    </div>

                    @if ($rankingVendedores->isEmpty())
                        <p class="py-12 text-center text-sm text-gray-400">Todavía no hay ventas este mes.</p>
                    @else
                        <ol class="space-y-3">
                            @foreach ($rankingVendedores as $puesto => $vendedor)
                                <li class="flex items-center gap-3">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-bold
                                        {{ $puesto === 0
                                            ? 'bg-emerald-600 text-white'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $puesto + 1 }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ $vendedor['nombre'] }}</p>
                                        <p class="text-xs text-gray-500 tabular-nums">{{ $vendedor['ventas'] }} {{ Str::plural('venta', $vendedor['ventas']) }}</p>
                                    </div>
                                    <span class="shrink-0 text-sm font-semibold tabular-nums text-gray-900 dark:text-gray-100">
                                        S/ {{ number_format($vendedor['total'], 2) }}
                                    </span>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>

            {{-- Stock bajo --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">Productos con stock bajo</h3>
                    <a href="{{ route('productos.index', ['stock_bajo' => 1]) }}" class="text-xs font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">Ver todos →</a>
                </div>

                @if ($productosStockBajo->isEmpty())
                    <p class="p-8 text-center text-sm text-gray-500 dark:text-gray-400">Ningún producto está por debajo de su stock mínimo.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs uppercase text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-5 py-3">Producto</th>
                                    <th class="px-5 py-3">Categoría</th>
                                    <th class="px-5 py-3 text-right">Stock</th>
                                    <th class="px-5 py-3 text-right">Mínimo</th>
                                    <th class="px-5 py-3">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($productosStockBajo as $producto)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                        <td class="px-5 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $producto->nombre }}</td>
                                        <td class="px-5 py-3">
                                            <span class="inline-flex rounded-full bg-gray-100 dark:bg-gray-700 px-2.5 py-1 text-xs font-medium text-gray-700 dark:text-gray-300">{{ $producto->categoria->nombre }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-right tabular-nums font-semibold text-red-600 dark:text-red-400">{{ $producto->stock }}</td>
                                        <td class="px-5 py-3 text-right tabular-nums text-gray-500">{{ $producto->stock_minimo }}</td>
                                        <td class="px-5 py-3"><x-stock-badge :producto="$producto" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const oscuro = document.documentElement.classList.contains('dark');
                const ejes = oscuro ? '#9ca3af' : '#6b7280';
                const rejilla = oscuro ? 'rgba(255,255,255,.07)' : 'rgba(0,0,0,.06)';
                const soles = (v) => 'S/ ' + Number(v).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                new Chart(document.getElementById('graficaVentas'), {
                    type: 'bar',
                    data: {
                        labels: @json($serieMensual->pluck('etiqueta')),
                        datasets: [{
                            label: 'Ventas',
                            data: @json($serieMensual->pluck('total')),
                            backgroundColor: '#059669',
                            hoverBackgroundColor: '#047857',
                            borderRadius: 4,
                            maxBarThickness: 40,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (c) => soles(c.parsed.y) } },
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: ejes } },
                            y: { beginAtZero: true, grid: { color: rejilla }, ticks: { color: ejes, callback: (v) => 'S/ ' + v } },
                        },
                    },
                });

                // Reparto por método de pago. Cada método conserva su color
                // aunque cambie de posición entre un mes y otro.
                const pagos = @json($porMetodoPago);
                if (pagos.some((p) => p.total > 0)) {
                    new Chart(document.getElementById('graficaPagos'), {
                        type: 'doughnut',
                        data: {
                            labels: pagos.map((p) => p.etiqueta),
                            datasets: [{
                                data: pagos.map((p) => p.total),
                                backgroundColor: ['#059669', '#7c3aed', '#0891b2', '#ea580c'],
                                borderWidth: 2,
                                borderColor: oscuro ? '#1f2937' : '#ffffff',
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '58%',
                            plugins: {
                                legend: { position: 'right', labels: { color: ejes, boxWidth: 12, padding: 14 } },
                                tooltip: {
                                    callbacks: {
                                        label: (c) => {
                                            const suma = c.dataset.data.reduce((a, b) => a + b, 0);
                                            const parte = suma > 0 ? Math.round((c.parsed / suma) * 100) : 0;
                                            return `${c.label}: ${soles(c.parsed)} (${parte}%)`;
                                        },
                                    },
                                },
                            },
                        },
                    });
                } else {
                    document.getElementById('graficaPagos').closest('div').innerHTML =
                        '<p class="py-12 text-center text-sm text-gray-400">Todavía no hay cobros este mes.</p>';
                }

                const canvasTop = document.getElementById('graficaTop');
                if (canvasTop) {
                    new Chart(canvasTop, {
                        type: 'bar',
                        data: {
                            labels: @json($masVendidos->pluck('nombre')),
                            datasets: [{
                                label: 'Unidades',
                                data: @json($masVendidos->pluck('unidades')),
                                backgroundColor: '#0d9488',
                                borderRadius: 4,
                                maxBarThickness: 22,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                x: { beginAtZero: true, grid: { color: rejilla }, ticks: { color: ejes, precision: 0 } },
                                y: { grid: { display: false }, ticks: { color: ejes, font: { size: 11 } } },
                            },
                        },
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
