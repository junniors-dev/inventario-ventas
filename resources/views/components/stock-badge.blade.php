@props(['producto'])

@php
    $esBajo = $producto->stock <= $producto->stock_minimo;
    $porReponer = ! $esBajo && $producto->stock <= $producto->stock_minimo * 1.5;

    [$clases, $texto] = match (true) {
        $esBajo => ['bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400', 'Stock bajo'],
        $porReponer => ['bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400', 'Por reponer'],
        default => ['bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400', 'Disponible'],
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {$clases}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
    {{ $texto }}
</span>
