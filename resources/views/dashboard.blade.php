@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div x-data="dashboardPage">
    <x-page-header title="Dashboard" subtitle="Ringkasan kondisi laboratorium Anda hari ini." />

    <div x-show="error" x-cloak class="mt-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4">
        <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" />
        <p class="text-sm font-medium text-rose-700" x-text="error"></p>
    </div>

    <div x-show="loading" x-cloak class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <template x-for="i in 8" :key="i">
            <div class="card animate-pulse p-5">
                <div class="h-3 w-24 rounded bg-zinc-100"></div>
                <div class="mt-3 h-7 w-14 rounded bg-zinc-100"></div>
            </div>
        </template>
    </div>

    <div x-show="!loading" x-cloak class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        <div class="card p-5 transition hover:shadow-card-hover">
            <div class="flex items-center justify-between gap-2">
                <p class="truncate text-[13px] font-medium text-zinc-500">Total Item</p>
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><x-icon name="package" class="h-4 w-4" /></div>
            </div>
            <p class="mt-2 font-display text-3xl font-bold tracking-tight text-zinc-900" x-text="fmt.fmtNum(stats.items)"></p>
        </div>

        <div class="card p-5 transition hover:shadow-card-hover">
            <div class="flex items-center justify-between gap-2">
                <p class="truncate text-[13px] font-medium text-zinc-500">Stok Menipis</p>
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600"><x-icon name="alert" class="h-4 w-4" /></div>
            </div>
            <p class="mt-2 font-display text-3xl font-bold tracking-tight text-zinc-900" x-text="fmt.fmtNum(stats.lowStock)"></p>
        </div>

        <div class="card p-5 transition hover:shadow-card-hover">
            <div class="flex items-center justify-between gap-2">
                <p class="truncate text-[13px] font-medium text-zinc-500">Perlu Kalibrasi</p>
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600"><x-icon name="thermometer" class="h-4 w-4" /></div>
            </div>
            <p class="mt-2 font-display text-3xl font-bold tracking-tight text-zinc-900" x-text="fmt.fmtNum(stats.needsCalibration)"></p>
        </div>

        <div class="card p-5 transition hover:shadow-card-hover">
            <div class="flex items-center justify-between gap-2">
                <p class="truncate text-[13px] font-medium text-zinc-500">Kedaluwarsa</p>
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600"><x-icon name="clock" class="h-4 w-4" /></div>
            </div>
            <p class="mt-2 font-display text-3xl font-bold tracking-tight text-zinc-900" x-text="fmt.fmtNum(stats.expired)"></p>
        </div>

        <div class="card p-5 transition hover:shadow-card-hover">
            <div class="flex items-center justify-between gap-2">
                <p class="truncate text-[13px] font-medium text-zinc-500">Peminjaman Diajukan</p>
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600"><x-icon name="clipboard-list" class="h-4 w-4" /></div>
            </div>
            <p class="mt-2 font-display text-3xl font-bold tracking-tight text-zinc-900" x-text="fmt.fmtNum(stats.pendingBorrowings)"></p>
        </div>

        <div class="card p-5 transition hover:shadow-card-hover">
            <div class="flex items-center justify-between gap-2">
                <p class="truncate text-[13px] font-medium text-zinc-500">Peminjaman Terlambat</p>
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600"><x-icon name="alert-circle" class="h-4 w-4" /></div>
            </div>
            <p class="mt-2 font-display text-3xl font-bold tracking-tight text-zinc-900" x-text="fmt.fmtNum(stats.overdueBorrowings)"></p>
        </div>

        <div class="card p-5 transition hover:shadow-card-hover">
            <div class="flex items-center justify-between gap-2">
                <p class="truncate text-[13px] font-medium text-zinc-500">Notifikasi Baru</p>
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-fuchsia-50 text-fuchsia-600"><x-icon name="bell" class="h-4 w-4" /></div>
            </div>
            <p class="mt-2 font-display text-3xl font-bold tracking-tight text-zinc-900" x-text="fmt.fmtNum(stats.unread)"></p>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
                <div>
                    <h2 class="font-display text-base font-semibold text-zinc-900">Aktivitas Terakhir</h2>
                    <p class="text-xs text-zinc-400">Audit trail sistem</p>
                </div>
                <a href="/audit-trails" class="text-xs font-semibold text-brand-600 transition hover:text-brand-700">Lihat semua</a>
            </div>
            <div class="divide-y divide-zinc-50">
                <template x-for="a in recentActivities" :key="a.id">
                    <div class="flex items-center gap-3 px-5 py-3.5">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-[11px] font-bold text-zinc-500" x-text="fmt.initials(a.user?.name ?? 'Sistem')"></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[13px] text-zinc-700">
                                <span class="font-semibold text-zinc-900" x-text="a.user?.name ?? 'Sistem'"></span>
                                <span x-text="` ${a.action} ${fmt.modelName(a.auditable_type)}`"></span>
                            </p>
                            <p class="mt-0.5 text-[11px] text-zinc-400" x-text="fmt.fmtDateTime(a.created_at)"></p>
                        </div>
                    </div>
                </template>
                <p x-show="!recentActivities.length && !loading" class="px-5 py-10 text-center text-sm text-zinc-400">Belum ada aktivitas.</p>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card">
                <div class="border-b border-zinc-100 px-5 py-4">
                    <h2 class="font-display text-base font-semibold text-zinc-900">Peminjaman Terbaru</h2>
                </div>
                <div class="divide-y divide-zinc-50">
                    <template x-for="b in recentBorrowings" :key="b.id">
                        <a :href="`/borrowings/${b.id}`" class="flex items-center gap-3 px-5 py-3 transition hover:bg-zinc-50">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-semibold text-zinc-900" x-text="b.request_number"></p>
                                <p class="truncate text-[11px] text-zinc-400" x-text="b.requested_by?.name"></p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(b.status)">
                                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-60"></span>
                                <span x-text="fmt.statusLabel(b.status)"></span>
                            </span>
                        </a>
                    </template>
                    <p x-show="!recentBorrowings.length && !loading" class="px-5 py-8 text-center text-sm text-zinc-400">Belum ada peminjaman.</p>
                </div>
            </div>

            <div class="card">
                <div class="border-b border-zinc-100 px-5 py-4">
                    <h2 class="font-display text-base font-semibold text-zinc-900">Pemakaian Terbaru</h2>
                </div>
                <div class="divide-y divide-zinc-50">
                    <template x-for="u in recentUsages" :key="u.id">
                        <div class="flex items-center gap-3 px-5 py-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[13px] font-semibold text-zinc-900" x-text="u.item?.name"></p>
                                <p class="truncate text-[11px] text-zinc-400" x-text="`${fmt.fmtNum(u.quantity_used)} · ${u.user?.name ?? '—'}`"></p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(u.status)">
                                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-60"></span>
                                <span x-text="fmt.statusLabel(u.status)"></span>
                            </span>
                        </div>
                    </template>
                    <p x-show="!recentUsages.length && !loading" class="px-5 py-8 text-center text-sm text-zinc-400">Belum ada pemakaian.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
