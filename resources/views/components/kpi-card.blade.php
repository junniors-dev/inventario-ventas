@props(['label', 'value', 'tone' => 'accent', 'icon' => null, 'footnote' => null])

@php
    $tonos = [
        'accent' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400',
        'warn' => 'bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400',
        'danger' => 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-400',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-5']) }}>
    @if ($icon)
        <div class="grid h-9 w-9 place-items-center rounded-lg {{ $tonos[$tone] ?? $tonos['accent'] }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">{{ $icon }}</svg>
        </div>
    @endif

    <p class="mt-3.5 text-sm font-medium text-gray-600 dark:text-gray-400">{{ $label }}</p>
    <p class="mt-0.5 text-2xl font-bold tabular-nums tracking-tight text-gray-900 dark:text-gray-100">{{ $value }}</p>

    @if ($footnote)
        <p class="mt-1.5 text-xs">{{ $footnote }}</p>
    @endif
</div>
