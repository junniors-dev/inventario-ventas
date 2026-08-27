<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Editar producto
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 sm:p-8">
                @include('productos.form', [
                    'producto' => $producto,
                    'categorias' => $categorias,
                    'action' => route('productos.update', $producto),
                    'method' => 'PATCH',
                    'submitLabel' => 'Actualizar producto',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
