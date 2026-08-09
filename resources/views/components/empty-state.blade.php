@props(['title' => 'Tidak ada data', 'subtitle' => 'Belum ada data untuk ditampilkan.', 'icon' => 'box'])

<div class="flex flex-col items-center justify-center px-6 py-16 text-center">
    <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-400">
        <x-icon :name="$icon" class="h-6 w-6" />
    </div>
    <h3 class="font-display text-base font-semibold text-zinc-900">{{ $title }}</h3>
    <p class="mt-1 max-w-sm text-sm text-zinc-500">{{ $subtitle }}</p>
    {{ $slot }}
</div>
