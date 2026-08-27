<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Nueva venta
        </h2>
    </x-slot>

    <div class="py-8" x-data="pos({{ Js::from($productos->map(fn ($p) => [
        'id' => $p->id,
        'nombre' => $p->nombre,
        'codigo' => $p->codigo_barras,
        'categoria' => $p->categoria->nombre,
        'precio' => (float) $p->precio,
        'stock' => $p->stock,
    ])) }})" @keydown.window.f2.prevent="enfocarBuscador()">
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
                    {{-- Buscador con lector de código de barras --}}
                    <div class="relative mb-4" @click.outside="cerrarSugerencias()">
                        <div class="flex items-center gap-2 rounded-lg bg-white dark:bg-gray-800 shadow-sm px-4 py-2.5
                                    ring-1 transition"
                             :class="escaneando ? 'ring-2 ring-emerald-500' : 'ring-transparent'">
                            <svg class="w-5 h-5 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" d="M3 5v14M7 5v14M11 5v14M15 5v10M19 5v14"/>
                            </svg>
                            <input x-ref="buscador"
                                   x-model="buscar"
                                   @input="alEscribir()"
                                   @keydown.enter.prevent="alPulsarEnter()"
                                   @keydown.arrow-down.prevent="moverResaltado(1)"
                                   @keydown.arrow-up.prevent="moverResaltado(-1)"
                                   @keydown.escape="cerrarSugerencias()"
                                   type="text"
                                   autocomplete="off"
                                   autofocus
                                   placeholder="Escanea un código o escribe el nombre…"
                                   aria-label="Buscar producto o escanear código de barras"
                                   role="combobox"
                                   :aria-expanded="sugerencias.length > 0"
                                   aria-controls="sugerencias-productos"
                                   class="w-full border-0 bg-transparent p-0 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-0">
                            <kbd class="hidden sm:inline shrink-0 rounded border border-gray-300 dark:border-gray-600 px-1.5 py-0.5 text-[10px] font-medium text-gray-500">F2</kbd>
                        </div>

                        {{-- Autocompletado --}}
                        <ul id="sugerencias-productos" role="listbox"
                            x-show="sugerencias.length > 0" x-cloak x-transition.opacity.duration.100ms
                            class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg">
                            <template x-for="(p, i) in sugerencias" :key="p.id">
                                <li role="option" :aria-selected="i === resaltado"
                                    @click="agregar(p.id); limpiarBuscador()"
                                    @mouseenter="resaltado = i"
                                    class="flex cursor-pointer items-center gap-3 px-4 py-2.5"
                                    :class="i === resaltado ? 'bg-emerald-50 dark:bg-emerald-950/50' : ''">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100" x-text="p.nombre"></p>
                                        <p class="text-xs text-gray-500">
                                            <span x-text="p.categoria"></span>
                                            <template x-if="p.codigo">
                                                <span class="font-mono"> · <span x-text="p.codigo"></span></span>
                                            </template>
                                        </p>
                                    </div>
                                    <span class="shrink-0 text-sm font-semibold tabular-nums text-gray-900 dark:text-gray-100"
                                          x-text="'S/ ' + p.precio.toFixed(2)"></span>
                                    <span class="shrink-0 text-xs tabular-nums"
                                          :class="disponible(p.id, p.stock) > 0 ? 'text-gray-400' : 'font-semibold text-red-500'"
                                          x-text="p.stock === 0 ? 'Agotado' : disponible(p.id, p.stock) + ' disp.'"></span>
                                </li>
                            </template>
                        </ul>

                        {{-- Aviso de escaneo --}}
                        <p x-show="aviso" x-cloak x-transition.opacity
                           class="absolute z-10 mt-1 w-full rounded-lg px-4 py-2 text-sm shadow-lg"
                           :class="avisoTipo === 'error'
                               ? 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300'
                               : 'bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'"
                           x-text="aviso" role="status"></p>
                    </div>

                    @if ($catalogoTruncado)
                        <div class="mb-4 rounded-lg bg-amber-50 dark:bg-amber-950/40 px-4 py-2.5 text-sm text-amber-800 dark:text-amber-300">
                            Se muestran los primeros {{ $productos->count() }} productos con stock. Usa el buscador para encontrar el resto.
                        </div>
                    @endif

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
                        <template x-for="p in catalogo" :key="p.id">
                            <button type="button"
                                    @click="agregar(p.id)"
                                    :disabled="disponible(p.id, p.stock) <= 0"
                                    class="flex flex-col gap-1 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-3 text-left shadow-sm transition hover:border-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="text-[10px] font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-500" x-text="p.categoria"></span>
                                <span class="text-sm font-semibold leading-tight text-gray-900 dark:text-gray-100" x-text="p.nombre"></span>
                                <span class="mt-1.5 flex items-center justify-between">
                                    <span class="tabular-nums font-semibold text-gray-900 dark:text-gray-100" x-text="'S/ ' + p.precio.toFixed(2)"></span>
                                    <span class="text-xs tabular-nums"
                                          :class="p.stock === 0 ? 'font-semibold text-red-500' : 'text-gray-500 dark:text-gray-400'"
                                          x-text="p.stock === 0 ? 'Agotado' : disponible(p.id, p.stock) + ' disp.'"></span>
                                </span>
                            </button>
                        </template>
                    </div>

                    <p x-show="catalogo.length === 0" x-cloak class="py-10 text-center text-sm text-gray-400">
                        No hay productos que coincidan.
                    </p>
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
                            <p class="px-4 py-10 text-center text-sm text-gray-400">Escanea un código o toca el catálogo</p>
                        </template>

                        <template x-for="(item, i) in items" :key="item.id">
                            <div class="flex items-center gap-2 rounded-lg px-2 py-2"
                                 :class="item.id === ultimoAgregado ? 'bg-emerald-50 dark:bg-emerald-950/40' : 'hover:bg-gray-50 dark:hover:bg-gray-700/40'">
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
                                               class="peer sr-only" @checked($loop->first)>
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
            function pos(productos) {
                return {
                    productos,
                    items: [],
                    buscar: '',
                    categoria: '',
                    resaltado: 0,
                    aviso: '',
                    avisoTipo: 'ok',
                    escaneando: false,
                    ultimoAgregado: null,
                    _tecleoPrevio: 0,
                    _temporizadorAviso: null,

                    // --- Catálogo y sugerencias -------------------------------
                    get catalogo() {
                        return this.productos.filter((p) => this.coincide(p));
                    },
                    get sugerencias() {
                        if (this.buscar.trim() === '') return [];
                        return this.productos.filter((p) => this.coincideTexto(p)).slice(0, 6);
                    },
                    coincideTexto(p) {
                        const q = this.buscar.trim().toLowerCase();
                        return p.nombre.toLowerCase().includes(q) || (p.codigo ?? '').includes(q);
                    },
                    coincide(p) {
                        return this.coincideTexto(p) && (this.categoria === '' || this.categoria === p.categoria);
                    },

                    // --- Carrito ----------------------------------------------
                    enCarrito(id) {
                        return this.items.find((i) => i.id === id)?.cantidad ?? 0;
                    },
                    disponible(id, stock) {
                        return stock - this.enCarrito(id);
                    },
                    agregar(id) {
                        const p = this.productos.find((x) => x.id === id);
                        if (!p) return false;

                        if (this.disponible(p.id, p.stock) <= 0) {
                            this.mostrarAviso(
                                p.stock === 0
                                    ? `${p.nombre} está agotado`
                                    : `Ya tienes las ${p.stock} unidades disponibles de ${p.nombre}`,
                                'error',
                            );
                            return false;
                        }

                        const item = this.items.find((i) => i.id === p.id);
                        if (item) {
                            item.cantidad++;
                        } else {
                            this.items.push({ ...p, cantidad: 1 });
                        }

                        this.ultimoAgregado = p.id;
                        setTimeout(() => { if (this.ultimoAgregado === p.id) this.ultimoAgregado = null; }, 700);
                        return true;
                    },
                    cambiar(id, delta) {
                        const item = this.items.find((i) => i.id === id);
                        if (!item) return;
                        if (delta > 0 && item.cantidad >= item.stock) {
                            this.mostrarAviso(`Solo quedan ${item.stock} de ${item.nombre}`, 'error');
                            return;
                        }
                        item.cantidad += delta;
                        if (item.cantidad <= 0) {
                            this.items = this.items.filter((i) => i.id !== id);
                        }
                    },
                    totalItems() {
                        return this.items.reduce((n, i) => n + i.cantidad, 0);
                    },
                    total() {
                        return this.items.reduce((s, i) => s + i.precio * i.cantidad, 0);
                    },

                    // --- Lector de códigos de barras --------------------------
                    // Un lector USB se comporta como un teclado: teclea muy
                    // rápido y termina con Enter. Si las pulsaciones llegan por
                    // debajo del umbral humano, se trata como escaneo.
                    alEscribir() {
                        const ahora = Date.now();
                        this.escaneando = ahora - this._tecleoPrevio < 40;
                        this._tecleoPrevio = ahora;
                        this.resaltado = 0;
                    },
                    alPulsarEnter() {
                        const termino = this.buscar.trim();
                        if (termino === '') return;

                        // 1) Coincidencia exacta de código: es un escaneo.
                        const escaneado = this.productos.find((p) => p.codigo === termino);
                        if (escaneado) {
                            if (this.agregar(escaneado.id)) {
                                this.mostrarAviso(`${escaneado.nombre} agregado`, 'ok');
                            }
                            this.limpiarBuscador();
                            return;
                        }

                        // 2) Sugerencia resaltada del autocompletado.
                        const sugerencia = this.sugerencias[this.resaltado];
                        if (sugerencia) {
                            this.agregar(sugerencia.id);
                            this.limpiarBuscador();
                            return;
                        }

                        // 3) Código desconocido: probablemente un producto sin
                        //    registrar o agotado (el catálogo solo trae con stock).
                        this.mostrarAviso(`Sin resultados para «${termino}»`, 'error');
                        this.limpiarBuscador();
                    },
                    moverResaltado(paso) {
                        if (this.sugerencias.length === 0) return;
                        const total = this.sugerencias.length;
                        this.resaltado = (this.resaltado + paso + total) % total;
                    },
                    cerrarSugerencias() {
                        this.buscar = '';
                        this.resaltado = 0;
                    },
                    limpiarBuscador() {
                        this.buscar = '';
                        this.resaltado = 0;
                        this.escaneando = false;
                        this.$refs.buscador.focus();
                    },
                    enfocarBuscador() {
                        this.$refs.buscador.focus();
                        this.$refs.buscador.select();
                    },
                    mostrarAviso(texto, tipo) {
                        this.aviso = texto;
                        this.avisoTipo = tipo;
                        clearTimeout(this._temporizadorAviso);
                        this._temporizadorAviso = setTimeout(() => { this.aviso = ''; }, 2500);
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
