<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Nueva venta
        </h2>
    </x-slot>

    <div class="py-8" x-data="pos()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 rounded-lg border-l-4 border-red-500 bg-red-50 dark:bg-red-950 p-4">
                    <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_360px] gap-6 items-start">

                {{-- Catálogo --}}
                <div>
                    <div class="flex items-center gap-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm px-4 py-2.5 mb-4">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                        <input x-model="buscar" type="search" placeholder="Buscar producto…"
                               class="w-full border-0 bg-transparent p-0 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-0">
                    </div>

                    <div class="flex flex-wrap gap-2 mb-4">
                        <button type="button" @click="categoria = ''"
                                :class="categoria === '' ? 'bg-emerald-50 text-emerald-700 border-transparent dark:bg-emerald-950 dark:text-emerald-400' : 'bg-white text-gray-600 border-gray-300 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700'"
                                class="rounded-full border px-3.5 py-1.5 text-xs font-semibold">Todas</button>
                        @foreach ($categorias as $categoria)
                            <button type="button" @click="categoria = '{{ $categoria->nombre }}'"
                                    :class="categoria === '{{ $categoria->nombre }}' ? 'bg-emerald-50 text-emerald-700 border-transparent dark:bg-emerald-950 dark:text-emerald-400' : 'bg-white text-gray-600 border-gray-300 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700'"
                                    class="rounded-full border px-3.5 py-1.5 text-xs font-semibold">{{ $categoria->nombre }}</button>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                        @foreach ($productos as $producto)
                            <button type="button"
                                    x-show="visible({{ Js::from($producto->nombre) }}, {{ Js::from($producto->categoria->nombre) }})"
                                    @click="agregar({{ $producto->id }}, {{ Js::from($producto->nombre) }}, {{ $producto->precio }}, {{ $producto->stock }})"
                                    :disabled="disponible({{ $producto->id }}, {{ $producto->stock }}) <= 0"
                                    class="flex flex-col gap-1 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 text-left shadow-sm transition hover:border-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="text-[10px] font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-500">{{ $producto->categoria->nombre }}</span>
                                <span class="text-sm font-semibold leading-tight text-gray-900 dark:text-gray-100">{{ $producto->nombre }}</span>
                                <span class="mt-1.5 flex items-center justify-between">
                                    <span class="tabular-nums font-semibold text-gray-900 dark:text-gray-100">S/ {{ number_format($producto->precio, 2) }}</span>
                                    <span class="text-xs tabular-nums text-gray-500 dark:text-gray-400"
                                          x-text="disponible({{ $producto->id }}, {{ $producto->stock }}) + ' disp.'"></span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Carrito --}}
                <form method="POST" action="{{ route('ventas.store') }}"
                      class="lg:sticky lg:top-6 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm flex flex-col">
                    @csrf

                    <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 px-4 py-3">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2 3h3l2.4 12.3a1.5 1.5 0 0 0 1.5 1.2h8.7a1.5 1.5 0 0 0 1.5-1.2L23 7H6"/></svg>
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">Carrito</h3>
                        <span class="ml-auto rounded-full bg-emerald-600 px-2 py-0.5 text-xs font-bold text-white tabular-nums" x-text="totalItems()"></span>
                    </div>

                    <div class="max-h-80 overflow-y-auto p-2">
                        <template x-if="items.length === 0">
                            <p class="px-4 py-10 text-center text-sm text-gray-400">Agrega productos tocando el catálogo</p>
                        </template>

                        <template x-for="(item, i) in items" :key="item.id">
                            <div class="flex items-center gap-2 rounded-lg px-2 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <input type="hidden" :name="`lineas[${i}][producto_id]`" :value="item.id">
                                <input type="hidden" :name="`lineas[${i}][cantidad]`" :value="item.cantidad">

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100" x-text="item.nombre"></p>
                                    <p class="text-xs tabular-nums text-gray-500" x-text="'S/ ' + item.precio.toFixed(2) + ' c/u'"></p>
                                </div>

                                <div class="flex items-center gap-0.5 rounded-lg bg-gray-100 dark:bg-gray-700 p-0.5">
                                    <button type="button" @click="cambiar(item.id, -1)" aria-label="Quitar uno"
                                            class="grid h-6 w-6 place-items-center rounded text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-600">−</button>
                                    <span class="w-6 text-center text-sm font-semibold tabular-nums text-gray-900 dark:text-gray-100" x-text="item.cantidad"></span>
                                    <button type="button" @click="cambiar(item.id, 1)" aria-label="Agregar uno"
                                            class="grid h-6 w-6 place-items-center rounded text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-600">+</button>
                                </div>

                                <span class="w-16 text-right text-sm font-semibold tabular-nums text-gray-900 dark:text-gray-100"
                                      x-text="'S/ ' + (item.precio * item.cantidad).toFixed(2)"></span>
                            </div>
                        </template>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 p-4 space-y-4">
                        <div>
                            <span class="mb-2 block text-[11px] font-semibold uppercase tracking-wide text-gray-500">Método de pago</span>
                            <div class="grid grid-cols-2 gap-1.5">
                                @foreach ($metodosPago as $metodo)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="metodo_pago" value="{{ $metodo->value }}"
                                               x-model="metodoPago" class="peer sr-only" @checked($loop->first)>
                                        <span class="block rounded-lg border border-gray-300 dark:border-gray-600 px-2 py-1.5 text-center text-xs font-semibold text-gray-600 dark:text-gray-400
                                                     peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700
                                                     dark:peer-checked:bg-emerald-950 dark:peer-checked:text-emerald-400">
                                            {{ $metodo->label() }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="space-y-1.5 text-sm">
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>Ítems</span><span class="tabular-nums" x-text="totalItems()"></span>
                            </div>
                            <div class="flex justify-between border-t border-dashed border-gray-300 dark:border-gray-600 pt-2 text-gray-900 dark:text-gray-100">
                                <span class="font-medium">Total</span>
                                <span class="text-xl font-bold tabular-nums" x-text="'S/ ' + total().toFixed(2)"></span>
                            </div>
                        </div>

                        <button type="submit" :disabled="items.length === 0"
                                class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed">
                            Confirmar venta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function pos() {
                return {
                    items: [],
                    buscar: '',
                    categoria: '',
                    metodoPago: '{{ $metodosPago[0]->value }}',

                    visible(nombre, cat) {
                        const coincideTexto = nombre.toLowerCase().includes(this.buscar.toLowerCase());
                        const coincideCat = this.categoria === '' || this.categoria === cat;
                        return coincideTexto && coincideCat;
                    },
                    enCarrito(id) {
                        return this.items.find(i => i.id === id)?.cantidad ?? 0;
                    },
                    disponible(id, stock) {
                        return stock - this.enCarrito(id);
                    },
                    agregar(id, nombre, precio, stock) {
                        if (this.disponible(id, stock) <= 0) return;
                        const item = this.items.find(i => i.id === id);
                        if (item) {
                            item.cantidad++;
                        } else {
                            this.items.push({ id, nombre, precio: Number(precio), cantidad: 1, stock });
                        }
                    },
                    cambiar(id, delta) {
                        const item = this.items.find(i => i.id === id);
                        if (!item) return;
                        if (delta > 0 && item.cantidad >= item.stock) return;
                        item.cantidad += delta;
                        if (item.cantidad <= 0) {
                            this.items = this.items.filter(i => i.id !== id);
                        }
                    },
                    totalItems() {
                        return this.items.reduce((n, i) => n + i.cantidad, 0);
                    },
                    total() {
                        return this.items.reduce((s, i) => s + i.precio * i.cantidad, 0);
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
