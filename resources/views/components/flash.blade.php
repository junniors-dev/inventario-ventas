@php
    $flashes = collect([
        ['type' => 'success', 'message' => session('success')],
        ['type' => 'error', 'message' => session('error')],
    ])->filter(fn ($f) => filled($f['message']));
@endphp

@if ($flashes->isNotEmpty())
    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-3 w-full max-w-sm">
        @foreach ($flashes as $flash)
            <div
                x-data="{ show: true }"
                x-show="show"
                x-init="setTimeout(() => show = false, 4000)"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-x-8"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-end="opacity-0 translate-x-8"
                class="flex items-start gap-3 rounded-xl border bg-white dark:bg-gray-800 shadow-lg px-4 py-3
                    {{ $flash['type'] === 'success'
                        ? 'border-l-4 border-emerald-500'
                        : 'border-l-4 border-red-500' }}"
                role="alert"
            >
                <div class="mt-0.5 {{ $flash['type'] === 'success' ? 'text-emerald-500' : 'text-red-500' }}">
                    @if ($flash['type'] === 'success')
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h16.9a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                    @endif
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-200 flex-1">{{ $flash['message'] }}</p>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" aria-label="Cerrar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endforeach
    </div>
@endif
