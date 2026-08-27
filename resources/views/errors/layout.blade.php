<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <main class="flex min-h-full items-center justify-center p-6">
        <div class="w-full max-w-md text-center">
            <p class="font-mono text-6xl font-semibold text-emerald-600 dark:text-emerald-500">@yield('codigo')</p>

            <h1 class="mt-4 text-2xl font-semibold text-gray-900 dark:text-gray-100">@yield('titulo')</h1>

            <p class="mt-2 text-gray-600 dark:text-gray-400">@yield('mensaje')</p>

            <div class="mt-8 flex items-center justify-center gap-3">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}"
                   class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700">
                    Volver
                </a>
                <a href="{{ url('/') }}"
                   class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Ir al inicio
                </a>
            </div>
        </div>
    </main>
</body>
</html>
