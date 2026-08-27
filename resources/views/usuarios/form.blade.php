@php($usuario = $usuario ?? null)

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method === 'PATCH')
        @method('PATCH')
    @endif

    <div>
        <x-input-label for="name" value="Nombre completo" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $usuario?->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="Correo electrónico" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                      :value="old('email', $usuario?->email)" required />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="role" value="Rol" />
        <select id="role" name="role" required
                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-emerald-500 focus:ring-emerald-500 rounded-md shadow-sm">
            @foreach ($roles as $rol)
                <option value="{{ $rol->value }}" @selected(old('role', $usuario?->role?->value) === $rol->value)>
                    {{ $rol->label() }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            El vendedor solo puede registrar ventas y consultar las suyas.
        </p>
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>

    <div class="border-t border-gray-200 dark:border-gray-700 pt-6 space-y-6">
        @if ($usuario)
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Deja la contraseña en blanco para conservar la actual.
            </p>
        @endif

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                          autocomplete="new-password" @required(! $usuario) />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                          class="mt-1 block w-full" autocomplete="new-password" @required(! $usuario) />
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button type="submit"
                class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('usuarios.index') }}"
           class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
            Cancelar
        </a>
    </div>
</form>
