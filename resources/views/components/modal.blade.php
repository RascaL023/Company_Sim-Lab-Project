@props(['open' => 'formOpen', 'title' => '', 'subtitle' => '', 'maxWidth' => 'max-w-xl', 'closeable' => true, 'titleExpr' => null, 'subtitleExpr' => null])

<div
    x-show="{{ $open }}"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-zinc-950/50 px-4 py-8 backdrop-blur-sm sm:py-14"
    @keydown.window.escape="{{ $closeable ? $open . ' = false' : 'return' }}"
>
    <div class="absolute inset-0" @click="{{ $closeable ? $open . ' = false' : 'return' }}"></div>
    <div class="relative w-full {{ $maxWidth }} card p-6 shadow-card-hover">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h3 class="font-display text-lg font-semibold text-zinc-900" @if ($titleExpr) x-text="{{ $titleExpr }}" @endif>{{ $title }}</h3>
                @if ($subtitleExpr)
                    <p class="mt-0.5 text-sm text-zinc-500" x-text="{{ $subtitleExpr }}"></p>
                @elseif ($subtitle)
                    <p class="mt-0.5 text-sm text-zinc-500">{{ $subtitle }}</p>
                @endif
            </div>
            @if ($closeable)
                <button
                    type="button"
                    @click="{{ $open }} = false"
                    class="rounded-lg p-1.5 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600"
                    aria-label="Tutup"
                >
                    <x-icon name="x" class="h-5 w-5" />
                </button>
            @endif
        </div>
        <div {{ $attributes }}>
            {{ $slot }}
        </div>
    </div>
</div>
