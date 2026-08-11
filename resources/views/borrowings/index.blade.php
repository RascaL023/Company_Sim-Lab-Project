@extends('layouts.app')

@section('title', 'Peminjaman')

@section('content')
<div x-data="borrowingsPage">
    <x-page-header title="Peminjaman" subtitle="Permintaan peminjaman alat dan bahan laboratorium.">
        <x-slot:actions>
            <a href="/borrowings/create" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Ajukan Peminjaman
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="card mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 p-4">
            <select x-model="filters.status" @change="applyFilters()" class="input !w-auto">
                <option value="">Semua status</option>
                <option value="diajukan">Diajukan</option>
                <option value="disetujui">Disetujui</option>
                <option value="ditolak">Ditolak</option>
                <option value="diproses">Diproses</option>
                <option value="selesai">Selesai</option>
                <option value="batal">Batal</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px]">
                <thead class="bg-zinc-50/70">
                    <tr>
                        <th class="table-th">No. Permintaan</th>
                        <th class="table-th">Pemohon</th>
                        <th class="table-th">Tujuan</th>
                        <th class="table-th">Item</th>
                        <th class="table-th">Diajukan</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="req in items" :key="req.id">
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="table-td">
                                <button type="button" @click="openDrawer(req)" class="font-mono text-sm font-semibold text-brand-700 transition hover:text-brand-800" x-text="req.request_number"></button>
                            </td>
                            <td class="table-td">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-[11px] font-bold text-zinc-500" x-text="fmt.initials(req.requested_by?.name)"></div>
                                    <div>
                                        <p class="text-sm font-medium text-zinc-800" x-text="req.requested_by?.name ?? '—'"></p>
                                        <p class="text-xs text-zinc-400" x-text="req.requested_by?.role"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-td max-w-[220px] truncate text-sm text-zinc-600" x-text="req.purpose ?? '—'"></td>
                            <td class="table-td text-sm text-zinc-600">
                                <p x-text="`${itemQuantity(req)} item`"></p>
                            </td>
                            <td class="table-td text-sm text-zinc-500" x-text="fmt.fmtDateTime(req.requested_at)"></td>
                            <td class="table-td">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(req.status)">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-60"></span>
                                    <span x-text="fmt.statusLabel(req.status)"></span>
                                </span>
                            </td>
                            <td class="table-td text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" class="btn btn-ghost btn-sm" title="Detail" @click="openDrawer(req)">
                                        <x-icon name="eye" class="h-3.5 w-3.5" />
                                    </button>
                                    <template x-if="req.status === 'diajukan' && $store.auth.isAny(['laboran'])">
                                        <button type="button" class="btn btn-ghost btn-sm !text-emerald-600 hover:!bg-emerald-50" title="Setujui" @click="approve(req)">
                                            <x-icon name="check" class="h-3.5 w-3.5" />
                                        </button>
                                    </template>
                                    <template x-if="req.status === 'diajukan' && $store.auth.isAny(['laboran'])">
                                        <button type="button" class="btn btn-ghost btn-sm !text-rose-500 hover:!bg-rose-50" title="Tolak" @click="openReject(req)">
                                            <x-icon name="x" class="h-3.5 w-3.5" />
                                        </button>
                                    </template>
                                    <template x-if="isCancellable(req)">
                                        <button type="button" class="btn btn-ghost btn-sm !text-amber-600 hover:!bg-amber-50" title="Batalkan" @click="cancel(req)">
                                            <x-icon name="ban" class="h-3.5 w-3.5" />
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div x-show="loading" class="flex items-center justify-center py-16">
                <div class="h-8 w-8 animate-spin rounded-full border-2 border-zinc-200 border-t-brand-600"></div>
            </div>

            <div x-show="!loading && !items.length" x-cloak>
                <x-empty-state title="Belum ada peminjaman" subtitle="Belum ada permintaan peminjaman yang cocok dengan filter." icon="clipboard-list" />
            </div>
        </div>

        <x-pagination />
    </div>

    {{-- Drawer detail --}}
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-zinc-900/40 backdrop-blur-[2px]" @click="drawerOpen = false"></div>
        <div class="absolute inset-y-0 right-0 flex w-full max-w-lg flex-col bg-white shadow-2xl" @keydown.escape.window="drawerOpen = false">
            <div class="flex items-start justify-between gap-4 border-b border-zinc-100 px-6 py-5">
                <div>
                    <p class="font-mono text-sm font-semibold text-brand-700" x-text="expanded?.request_number"></p>
                    <h3 class="mt-1 font-display text-lg font-bold text-zinc-900" x-text="expanded?.requested_by?.name"></h3>
                    <p class="mt-0.5 text-sm text-zinc-500" x-text="expanded?.purpose"></p>
                </div>
                <button type="button" class="btn btn-ghost btn-sm !p-2" @click="drawerOpen = false">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(expanded?.status)">
                        <span x-text="fmt.statusLabel(expanded?.status)"></span>
                    </span>
                    <span class="text-xs text-zinc-400" x-text="`Diajukan ${fmt.fmtDateTime(expanded?.requested_at)}`"></span>
                </div>

                <template x-if="expanded?.rejection_reason">
                    <div class="mt-4 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4">
                        <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" />
                        <div>
                            <p class="text-sm font-semibold text-rose-700">Alasan penolakan</p>
                            <p class="mt-0.5 text-sm text-rose-600" x-text="expanded.rejection_reason"></p>
                        </div>
                    </div>
                </template>

                <p class="mt-6 mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">Daftar item</p>
                <div class="space-y-3">
                    <template x-for="bi in expanded?.items ?? []" :key="bi.id">
                        <div class="rounded-2xl border border-zinc-100 p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-zinc-900" x-text="bi.item?.name ?? '—'"></p>
                                    <p class="mt-0.5 text-xs text-zinc-400" x-text="`${bi.item?.code} · Qty ${fmt.fmtNum(bi.quantity)}`"></p>
                                    <p x-show="bi.item_unit?.serial_number" class="mt-0.5 font-mono text-xs text-zinc-500" x-text="`Unit: ${bi.item_unit.serial_number}`"></p>
                                </div>
                                <div class="flex shrink-0 flex-wrap justify-end gap-1.5">
                                    <template x-if="expanded?.status === 'disetujui' && !bi.borrow_date && $store.auth.isAny(['laboran'])">
                                        <button type="button" class="btn btn-primary btn-sm" @click="openCheckout(bi)">
                                            <x-icon name="package" class="h-3.5 w-3.5" />
                                            Checkout
                                        </button>
                                    </template>
                                    <template x-if="bi.borrow_date && !bi.actual_return_date && $store.auth.isAny(['laboran'])">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="openReturn(bi)">
                                            <x-icon name="rotate-ccw" class="h-3.5 w-3.5" />
                                            Return
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <div x-show="bi.actual_return_date" class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 border-t border-zinc-50 pt-3 text-xs text-zinc-500">
                                <span x-text="`Kembali ${fmt.fmtDate(bi.actual_return_date)}`"></span>
                                <span x-show="bi.condition_after" x-text="`Kondisi ${fmt.conditionLabel(bi.condition_after)}`"></span>
                                <span x-show="bi.is_damaged" class="font-medium text-rose-600">Rusak</span>
                            </div>
                            <div x-show="bi.borrow_date && !bi.actual_return_date" class="mt-3 border-t border-zinc-50 pt-3 text-xs text-zinc-500">
                                <span x-text="`Dipinjam ${fmt.fmtDateTime(bi.borrow_date)}`"></span>
                                <span x-show="bi.expected_return_date" x-text="` · Estimasi kembali ${fmt.fmtDate(bi.expected_return_date)}`"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Reject modal --}}
    <x-modal open="rejectOpen" title="Tolak Peminjaman" subtitle="Sampaikan alasan penolakan permintaan ini.">
        <div class="grid gap-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Alasan penolakan</label>
                <textarea x-model="rejectionReason" rows="3" class="input" placeholder="Alasan penolakan..."></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="rejectOpen = false">Batal</button>
                <button type="button" class="btn btn-danger" :disabled="busy || !rejectionReason.trim()" @click="reject()">Tolak Permintaan</button>
            </div>
        </div>
    </x-modal>

    {{-- Checkout modal --}}
    <x-modal open="checkoutOpen" title="Checkout Item" subtitle="Serahkan item kepada peminjam.">
        <div class="grid gap-4">
            <template x-if="checkoutTarget?.item?.is_alat">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Unit fisik yang diserahkan</label>
                    <select x-model="checkoutUnitId" class="input">
                        <option value="">— Pilih unit —</option>
                        <template x-for="u in checkoutUnits" :key="u.id">
                            <option :value="u.id" x-text="`${unitLabel(u)}${u.location?.name ? ' · ' + u.location.name : ''}`"></option>
                        </template>
                    </select>
                    <p x-show="unitsLoading" class="mt-1 text-xs text-zinc-400">Memuat unit tersedia...</p>
                    <p x-show="!unitsLoading && !checkoutUnits.length" class="mt-1 text-xs font-medium text-amber-600">Tidak ada unit tersedia untuk item ini.</p>
                </div>
            </template>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Estimasi tanggal kembali</label>
                <input type="date" x-model="expectedReturnDate" class="input" />
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="checkoutOpen = false">Batal</button>
                <button type="button" class="btn btn-primary" :disabled="busy || !expectedReturnDate || (checkoutTarget?.item?.is_alat && !checkoutUnitId)" @click="doCheckout()">Konfirmasi Checkout</button>
            </div>
        </div>
    </x-modal>

    {{-- Return modal --}}
    <x-modal open="returnOpen" title="Return Item" subtitle="Periksa dan catat kondisi item yang dikembalikan.">
        <div class="grid gap-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kondisi setelah peminjaman</label>
                <select x-model="returnForm.condition_after" class="input">
                    <option value="baik">Baik</option>
                    <option value="rusak_ringan">Rusak ringan</option>
                    <option value="rusak_berat">Rusak berat</option>
                    <option value="hilang">Hilang</option>
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm text-zinc-700">
                <input type="checkbox" x-model="returnForm.is_damaged" class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
                Item rusak
            </label>
            <div x-show="returnForm.is_damaged">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Catatan kerusakan</label>
                <textarea x-model="returnForm.damage_notes" rows="3" class="input" placeholder="Deskripsi kerusakan..."></textarea>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Catatan pemeriksaan</label>
                <textarea x-model="returnForm.check_notes" rows="3" class="input" placeholder="Hasil pemeriksaan..."></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="returnOpen = false">Batal</button>
                <button type="button" class="btn btn-primary" :disabled="busy || (returnForm.is_damaged && !returnForm.damage_notes.trim())" @click="doReturn()">Konfirmasi Return</button>
            </div>
        </div>
    </x-modal>
</div>
@endsection
