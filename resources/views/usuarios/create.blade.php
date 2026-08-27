<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Nuevo usuario
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 sm:p-8">
                @include('usuarios.form', [
                    'roles' => $roles,
                    'action' => route('usuarios.store'),
                    'method' => 'POST',
                    'submitLabel' => 'Crear usuario',
                ])
            </div>
        </div>
    </div>
</x-app-layout>
