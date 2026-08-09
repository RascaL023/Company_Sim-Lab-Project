@extends('layouts.app')

@section('title', 'Notifikasi')

<div x-data="notificationsPage">
    <x-page-header title="Notifikasi" subtitle="Pemberitahuan status dan aktivitas terkait akun Anda." />

    <div class="mx-auto mt-6 max-w-3xl">
        <div x-show="loading" class="flex items-center justify-center py-16">
            <div class="h-8 w-8 animate-spin rounded-full border-2 border-zinc-200 border-t-brand-600"></div>
        </div>

        <div class="card divide-y divide-zinc-50 overflow-hidden">
            <template x-for="n in items" :key="n.id">
                <a :href="notifTarget(n)" @click="markRead(n)" class="flex items-start gap-4 px-5 py-4 transition hover:bg-zinc-50/70">
                    <div class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-full" :class="n.read_at ? 'bg-zinc-100 text-zinc-400' : 'bg-brand-50 text-brand-600'">
                        <x-icon name="bell" class="h-4 w-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm leading-relaxed text-zinc-700" :class="!n.read_at ? 'font-semibold text-zinc-900' : ''" x-text="n.payload?.message ?? 'Pemberitahuan'"></p>
                        <p class="mt-1 text-xs text-zinc-400" x-text="fmt.ago(n.created_at)"></p>
                    </div>
                    <span x-show="!n.read_at" class="mt-2 h-2 w-2 shrink-0 rounded-full bg-brand-500"></span>
                </a>
            </template>
        </div>

        <div x-show="!loading && !items.length" x-cloak class="card">
            <x-empty-state title="Tidak ada notifikasi" subtitle="Belum ada notifikasi untuk Anda." icon="bell" />
        </div>

        <div x-show="meta?.last_page > 1" x-cloak class="mt-4 flex items-center justify-between">
            <p class="text-xs text-zinc-400" x-text="`Menampilkan ${meta.from ?? 0}–${meta.to ?? 0} dari ${meta.total ?? 0}`"></p>
            <div class="flex items-center gap-1">
                <button type="button" class="btn btn-secondary btn-sm" :disabled="meta.current_page <= 1" @click="setPage(meta.current_page - 1)">
                    <x-icon name="chevron-left" class="h-3.5 w-3.5" />
                </button>
                <button type="button" class="btn btn-secondary btn-sm" :disabled="meta.current_page >= meta.last_page" @click="setPage(meta.current_page + 1)">
                    <x-icon name="chevron-right" class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>
    </div>
</div>
