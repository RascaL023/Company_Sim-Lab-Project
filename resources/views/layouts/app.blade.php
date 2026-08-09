<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · SIMLab</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|space-grotesk:500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="appShell" class="min-h-screen bg-[#fafafa] text-zinc-900 antialiased">
    <x-toasts />
    <x-confirm />

    {{-- Mobile sidebar overlay --}}
    <div
        x-show="sidebarOpen"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-30 bg-zinc-950/40 backdrop-blur-sm lg:hidden"
        @click="sidebarOpen = false"
    ></div>

    {{-- Sidebar --}}
    <aside
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-zinc-200/80 bg-white transition-transform duration-200 lg:translate-x-0"
    >
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-zinc-100 px-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 font-display text-base font-bold text-white shadow-sm shadow-brand-600/30">S</div>
            <div>
                <p class="font-display text-[15px] font-bold leading-tight text-zinc-900">SIMLab</p>
                <p class="text-[11px] text-zinc-400">Sistem Manajemen Lab</p>
            </div>
        </div>

        <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-5">
            <div class="space-y-1">
                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-widest text-zinc-400">Umum</p>
                <x-nav-item href="/dashboard" label="Dashboard" match="dashboard" icon="dashboard" />
            </div>

            <div class="space-y-1">
                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-widest text-zinc-400">Katalog</p>
                <x-nav-item href="/items" label="Items" match="items*" icon="package" />
                <x-nav-item href="/categories" label="Kategori" match="categories*" icon="tag" />
                <x-nav-item href="/locations" label="Lokasi" match="locations*" icon="map-pin" />
                <x-nav-item href="/item-units" label="Unit Item" match="item-units*" icon="layers" />
            </div>

            <div class="space-y-1">
                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-widest text-zinc-400">Transaksi</p>
                <x-nav-item href="/borrowings" label="Peminjaman" match="borrowings*" icon="clipboard-list" />
                <x-nav-item href="/usages" label="Pemakaian" match="usages*" icon="beaker" />
                <x-nav-item href="/stock-opname" label="Stock Opname" match="stock-opname*" icon="clipboard-check" :roles="['laboran', 'admin_sistem']" />
                <x-nav-item href="/disposals" label="Disposal Aset" match="disposals*" icon="trash" :roles="['laboran', 'admin_sistem', 'kepala_lab']" />
            </div>

            <div class="space-y-1">
                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-widest text-zinc-400">Operasional</p>
                <x-nav-item href="/stock-movements" label="Mutasi Stok" match="stock-movements*" icon="activity" :roles="['laboran', 'admin_sistem']" />
                <x-nav-item href="/calibrations" label="Kalibrasi" match="calibrations*" icon="thermometer" :roles="['laboran', 'admin_sistem']" />
                <x-nav-item href="/maintenances" label="Maintenance" match="maintenances*" icon="wrench" :roles="['laboran', 'admin_sistem']" />
                <x-nav-item href="/attachments" label="Lampiran" match="attachments*" icon="paperclip" :roles="['laboran', 'admin_sistem', 'kepala_lab']" />
                <x-nav-item href="/audit-trails" label="Audit Trail" match="audit-trails*" icon="scroll" :roles="['kepala_lab', 'admin_sistem']" />
            </div>

            <div class="space-y-1">
                <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-widest text-zinc-400">Administrasi</p>
                <x-nav-item href="/users" label="Pengguna" match="users*" icon="users" :roles="['admin_sistem']" />
                <x-nav-item href="/reports" label="Laporan" match="reports*" icon="download" :roles="['laboran', 'kepala_lab', 'admin_sistem']" />
            </div>
        </nav>

        <div class="shrink-0 border-t border-zinc-100 p-4">
            <a href="/notifications" class="mb-2 flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13.5px] font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900">
                <x-icon name="bell" class="h-[18px] w-[18px] text-zinc-400" />
                Notifikasi
                <span x-show="unread > 0" x-cloak class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1.5 text-[11px] font-semibold text-white" x-text="unread"></span>
            </a>
            <div class="flex items-center gap-3 rounded-xl border border-zinc-100 bg-zinc-50/70 px-3 py-2.5">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-bold text-white" x-text="$store.auth.initials"></div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-[13px] font-semibold text-zinc-900" x-text="$store.auth.user?.name"></p>
                    <p class="truncate text-[11px] text-zinc-400" x-text="$store.auth.roleLabel"></p>
                </div>
                <button type="button" @click="$store.auth.logout()" title="Keluar" class="rounded-lg p-1.5 text-zinc-400 transition hover:bg-rose-50 hover:text-rose-600">
                    <x-icon name="logout" class="h-4 w-4" />
                </button>
            </div>
        </div>
    </aside>

    {{-- Main column --}}
    <div class="flex min-h-screen flex-col lg:pl-72">
        <header class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b border-zinc-200/70 bg-[#fafafa]/85 px-4 backdrop-blur sm:px-8">
            <button type="button" class="rounded-lg p-2 text-zinc-500 transition hover:bg-zinc-100 lg:hidden" @click="sidebarOpen = true" aria-label="Buka menu">
                <x-icon name="menu" class="h-5 w-5" />
            </button>
            <div class="flex min-w-0 items-center gap-2 text-sm">
                <a href="/dashboard" class="shrink-0 font-medium text-zinc-400 transition hover:text-zinc-600">SIMLab</a>
                <span class="text-zinc-300">/</span>
                <span class="truncate font-semibold text-zinc-800">@yield('title')</span>
            </div>

            <div class="ml-auto flex items-center gap-1">
                <div x-data="dropdown" @click.outside="close()" class="relative">
                    <button
                        type="button"
                        @click="toggle(); open && toggleNotif()"
                        class="relative rounded-xl p-2 text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
                        aria-label="Notifikasi"
                    >
                        <x-icon name="bell" class="h-5 w-5" />
                        <span
                            x-show="unread > 0"
                            x-cloak
                            class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
                            x-text="unread"
                        ></span>
                    </button>

                    <div
                        x-show="open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="absolute right-0 z-30 mt-2 w-80 overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-card-hover"
                    >
                        <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
                            <p class="text-sm font-semibold text-zinc-900">Notifikasi</p>
                            <a href="/notifications" class="text-xs font-semibold text-brand-600 transition hover:text-brand-700">Lihat semua</a>
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            <template x-for="n in notifs" :key="n.id">
                                <a
                                    :href="notifTarget(n)"
                                    @click="markRead(n)"
                                    class="flex gap-3 border-b border-zinc-50 px-4 py-3 transition hover:bg-zinc-50"
                                >
                                    <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" :class="n.read_at ? 'bg-zinc-200' : 'bg-brand-500'"></span>
                                    <div class="min-w-0">
                                        <p class="text-[13px] leading-snug text-zinc-700" x-text="n.payload?.message"></p>
                                        <p class="mt-0.5 text-[11px] text-zinc-400" x-text="fmt.ago(n.created_at)"></p>
                                    </div>
                                </a>
                            </template>
                            <p x-show="!notifs.length" class="px-4 py-8 text-center text-sm text-zinc-400">Belum ada notifikasi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 px-4 py-8 sm:px-8 lg:px-10">
            <div class="mx-auto w-full max-w-6xl">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
