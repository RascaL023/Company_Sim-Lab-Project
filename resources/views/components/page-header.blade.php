@props(['title' => '', 'subtitle' => ''])

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-zinc-900">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-zinc-500">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
