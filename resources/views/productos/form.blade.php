@php($producto = $producto ?? null)

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method === 'PATCH')
        @method('PATCH')
    @endif

    <div>
        <x-input-label for="nombre" value="Nombre del producto" />
        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full"
                      :value="old('nombre', $producto?->nombre)" required autofocus
                      placeholder="Ej. Arroz Costeño 1kg" />
        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="categoria_id" value="Categoría" />
        <select id="categoria_id" name="categoria_id" required
                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm">
            <option value="">— Selecciona una categoría —</option>
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->id }}" @selected(old('categoria_id', $producto?->categoria_id) == $categoria->id)>
                    {{ $categoria->nombre }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('categoria_id')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <x-input-label for="precio" value="Precio (S/)" />
            <x-text-input id="precio" name="precio" type="number" step="0.01" min="0.01" class="mt-1 block w-full"
                          :value="old('precio', $producto?->precio)" required placeholder="0.00" />
            <x-input-error :messages="$errors->get('precio')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="stock" value="Stock actual" />
            <x-text-input id="stock" name="stock" type="number" min="0" class="mt-1 block w-full"
                          :value="old('stock', $producto?->stock ?? 0)" required />
            <x-input-error :messages="$errors->get('stock')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="stock_minimo" value="Stock mínimo" />
            <x-text-input id="stock_minimo" name="stock_minimo" type="number" min="0" class="mt-1 block w-full"
                          :value="old('stock_minimo', $producto?->stock_minimo ?? 0)" required />
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Alerta bajo este nivel</p>
            <x-input-error :messages="$errors->get('stock_minimo')" class="mt-2" />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit"
                class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('productos.index') }}"
           class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
            Cancelar
        </a>
    </div>
</form>
