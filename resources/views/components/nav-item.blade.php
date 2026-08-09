@props(['href', 'label', 'match', 'icon', 'roles' => []])

@php
    $active = request()->path() === '/'
        ? $match === 'dashboard'
        : \Illuminate\Support\Str::is($match, request()->path());
@endphp

<a
    href="{{ $href }}"
    @if($roles) x-show="$store.auth.isAny(@js($roles))" @endif
    class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13.5px] font-medium transition-all {{ $active ? 'bg-brand-50/90 text-brand-700' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900' }}"
>
    <x-icon :name="$icon" class="h-[18px] w-[18px] shrink-0 {{ $active ? 'text-brand-600' : 'text-zinc-400 group-hover:text-zinc-600' }}" />
    <span>{{ $label }}</span>
    @if($active)
        <span class="ml-auto h-1.5 w-1.5 rounded-full bg-brand-500"></span>
    @endif
</a>
