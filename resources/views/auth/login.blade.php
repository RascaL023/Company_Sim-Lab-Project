@extends('layouts.guest')

@section('title', 'Masuk')

<div x-data="loginPage">
    <div class="mb-8 lg:hidden">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-display text-lg font-bold text-white">S</div>
            <div>
                <p class="font-display text-lg font-bold text-zinc-900">SIMLab</p>
                <p class="text-xs text-zinc-400">Sistem Manajemen Laboratorium</p>
            </div>
        </div>
    </div>

    <h1 class="font-display text-2xl font-bold tracking-tight text-zinc-900">Masuk ke akun Anda</h1>
    <p class="mt-1.5 text-sm text-zinc-500">Gunakan akun laboratorium yang telah diberikan oleh administrator.</p>

    <div x-show="error" x-cloak class="mt-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4">
        <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" />
        <p class="text-sm font-medium text-rose-700" x-text="error"></p>
    </div>

    <form class="mt-6 space-y-4" @submit.prevent="submit()">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-zinc-700">Email</label>
            <input type="email" x-model="email" :class="errors.email ? 'input input-error' : 'input'" placeholder="nama@wiralab.com" autocomplete="email" autofocus />
            <p x-show="errors.email" class="mt-1 text-xs text-rose-600" x-text="errors.email?.[0]"></p>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-zinc-700">Password</label>
            <input type="password" x-model="password" :class="errors.password ? 'input input-error' : 'input'" placeholder="••••••••" autocomplete="current-password" />
            <p x-show="errors.password" class="mt-1 text-xs text-rose-600" x-text="errors.password?.[0]"></p>
        </div>
        <button type="submit" :disabled="busy" class="btn btn-primary w-full py-3">
            <span x-show="busy" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
            <span x-show="!busy">Masuk</span>
        </button>
    </form>

    <div class="mt-8">
        <div class="flex items-center gap-3 text-[11px] font-medium uppercase tracking-widest text-zinc-400">
            <span class="h-px flex-1 bg-zinc-200"></span>
            Demo cepat · kredensial seed
            <span class="h-px flex-1 bg-zinc-200"></span>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-2">
            <template x-for="acc in quickLogin" :key="acc.email">
                <button type="button" @click="quick(acc.email)" class="btn btn-secondary justify-between !px-3 text-xs" :disabled="busy">
                    <span x-text="acc.label"></span>
                    <x-icon name="arrow-right" class="h-3.5 w-3.5 text-zinc-400" />
                </button>
            </template>
        </div>
    </div>
</div>
