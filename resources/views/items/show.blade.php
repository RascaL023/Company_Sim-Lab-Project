@extends('layouts.app')

@section('title', 'Detail Item')

@section('content')
<div x-data="itemDetailPage({ id: '{{ $item }}' })">
    <a href="/items" class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 transition hover:text-brand-600">
        <x-icon name="chevron-left" class="h-4 w-4" />
        Kembali ke Items
    </a>

    <div x-show="error" x-cloak class="mt-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4">
        <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" />
        <p class="text-sm font-medium text-rose-700" x-text="error"></p>
    </div>

    <div x-show="loading" x-cloak class="mt-6 animate-pulse">
        <div class="card p-6">
            <div class="h-6 w-64 rounded bg-zinc-100"></div>
            <div class="mt-3 h-4 w-40 rounded bg-zinc-100"></div>
        </div>
    </div>

    <template x-if="item && !loading">
        <div>
            <div class="mt-6 rounded-2xl border border-zinc-100 bg-white p-6 shadow-card">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-lg bg-zinc-100 px-2 py-0.5 font-mono text-xs font-medium text-zinc-600" x-text="item.code"></span>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(item.type)">
                                <span x-text="fmt.typeLabel(item.type)"></span>
                            </span>
                            <span x-show="item.is_low_stock" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">Stok menipis</span>
                            <span x-show="item.is_alat && item.needs_calibration" class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-700 ring-1 ring-inset ring-violet-600/20">Perlu kalibrasi</span>
                        </div>
                        <h1 class="mt-3 font-display text-2xl font-bold tracking-tight text-zinc-900" x-text="item.name"></h1>
                        <p class="mt-1 text-sm text-zinc-500" x-text="item.category?.name"></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        {{-- Alat: jumlah unit & unit tersedia --}}
                        <template x-if="item.is_alat">
                            <div class="flex flex-wrap items-center gap-3">
                                <div class="rounded-2xl bg-brand-50 px-5 py-3 text-center">
                                    <p class="font-display text-2xl font-bold text-brand-700" x-text="fmt.fmtNum(unitCounts.total ?? 0)"></p>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-brand-500">Jumlah unit</p>
                                </div>
                                <div class="rounded-2xl bg-emerald-50 px-5 py-3 text-center">
                                    <p class="font-display text-2xl font-bold text-emerald-700" x-text="fmt.fmtNum(unitCounts.available ?? 0)"></p>
                                    <p class="text-[11px] font-medium uppercase tracking-wide text-emerald-500">Unit tersedia</p>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <button type="button" class="btn btn-primary btn-sm" @click="setTab('units')">
                                        <x-icon name="eye" class="h-3.5 w-3.5" /> Lihat Daftar Unit
                                    </button>
                                    <template x-if="$store.auth.isAny(['laboran', 'admin_sistem'])">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="openUnitCreate()">
                                            <x-icon name="plus" class="h-3.5 w-3.5" /> Tambah Unit
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                        {{-- Bahan: stok --}}
                        <template x-if="item.is_bahan">
                            <div class="rounded-2xl bg-brand-50 px-5 py-3 text-center">
                                <p class="font-display text-2xl font-bold text-brand-700" x-text="`${fmt.fmtNum(item.stock_quantity)} ${item.unit}`"></p>
                                <p class="text-[11px] font-medium uppercase tracking-wide text-brand-500">Stok tersedia</p>
                            </div>
                        </template>
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-zinc-100 pt-6 sm:grid-cols-3 lg:grid-cols-4">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-zinc-400">Tipe</dt>
                        <dd class="mt-1 text-sm font-medium text-zinc-800" x-text="fmt.typeLabel(item.type)"></dd>
                    </div>
                    <template x-if="item.is_bahan">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-400">Stok minimum</dt>
                            <dd class="mt-1 text-sm font-medium text-zinc-800" x-text="`${fmt.fmtNum(item.minimum_stock)} ${item.unit}`"></dd>
                        </div>
                    </template>
                    <template x-if="item.is_bahan">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-400">Lokasi (katalog)</dt>
                            <dd class="mt-1 text-sm font-medium text-zinc-800" x-text="item.location?.name || '—'"></dd>
                        </div>
                    </template>
                    <template x-if="item.is_alat">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-400">Lokasi unit</dt>
                            <dd class="mt-1 text-sm font-medium text-zinc-800" x-text="`Per-unit (lihat tab Unit)`"></dd>
                        </div>
                    </template>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-zinc-400">Manufacturer</dt>
                        <dd class="mt-1 text-sm font-medium text-zinc-800" x-text="item.manufacturer || '—'"></dd>
                    </div>
                    <template x-if="item.description">
                        <div class="col-span-2 sm:col-span-3 lg:col-span-4">
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-400">Deskripsi</dt>
                            <dd class="mt-1 text-sm text-zinc-600" x-text="item.description"></dd>
                        </div>
                    </template>
                </dl>
            </div>

            <div class="mt-6">
                <div class="flex flex-wrap gap-1 border-b border-zinc-100">
                    <button type="button" x-show="item.is_alat" @click="setTab('units')" class="px-4 py-2.5 text-sm font-medium transition" :class="tab === 'units' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-zinc-500 hover:text-zinc-800'">Unit</button>
                    <button type="button" @click="setTab('movements')" class="px-4 py-2.5 text-sm font-medium transition" :class="tab === 'movements' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-zinc-500 hover:text-zinc-800'">Mutasi Stok</button>
                    <button type="button" @click="setTab('usages')" class="px-4 py-2.5 text-sm font-medium transition" :class="tab === 'usages' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-zinc-500 hover:text-zinc-800'">Pemakaian</button>
                    <button type="button" x-show="item.is_alat" @click="setTab('calibrations')" class="px-4 py-2.5 text-sm font-medium transition" :class="tab === 'calibrations' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-zinc-500 hover:text-zinc-800'">Kalibrasi</button>
                    <button type="button" x-show="item.is_alat" @click="setTab('maintenances')" class="px-4 py-2.5 text-sm font-medium transition" :class="tab === 'maintenances' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-zinc-500 hover:text-zinc-800'">Perawatan</button>
                    <button type="button" @click="setTab('audit')" class="px-4 py-2.5 text-sm font-medium transition" :class="tab === 'audit' ? 'border-b-2 border-brand-600 text-brand-700' : 'text-zinc-500 hover:text-zinc-800'">Audit Trail</button>
                </div>

                <div class="card mt-4 overflow-hidden">
                    <div x-show="tab === 'units' && item.is_alat" x-cloak class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-4 py-3">
                        <p class="text-sm font-medium text-zinc-600">Unit fisik alat ini</p>
                        <template x-if="$store.auth.isAny(['laboran', 'admin_sistem'])">
                            <button type="button" class="btn btn-primary btn-sm" @click="openUnitCreate()">
                                <x-icon name="plus" class="h-3.5 w-3.5" />
                                Tambah Unit
                            </button>
                        </template>
                    </div>
                    <div class="overflow-x-auto">
                        {{-- Units --}}
                        <table x-show="tab === 'units'" class="w-full min-w-[640px]">
                            <thead class="bg-zinc-50/70">
                                <tr>
                                    <th class="table-th">Unit</th>
                                    <th class="table-th">Kondisi</th>
                                    <th class="table-th">Lokasi</th>
                                    <th class="table-th">Status</th>
                                    <th class="table-th text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                <template x-for="u in tabItems" :key="u.id">
                                    <tr class="transition hover:bg-zinc-50/60">
                                        <td class="table-td">
                                            <p class="font-medium text-zinc-800" x-text="u.serial_number ?? u.asset_tag ?? 'Unit'"></p>
                                            <p x-show="u.serial_number && u.asset_tag" class="mt-0.5 text-xs text-zinc-400" x-text="u.asset_tag"></p>
                                        </td>
                                        <td class="table-td">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(u.condition)">
                                                <span x-text="fmt.conditionLabel(u.condition)"></span>
                                            </span>
                                        </td>
                                        <td class="table-td text-sm text-zinc-600" x-text="u.location?.name ?? '—'"></td>
                                        <td class="table-td">
                                            <div class="flex flex-wrap gap-1">
                                                <span x-show="u.is_borrowed" class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">Dipinjam</span>
                                                <span x-show="u.needs_calibration" class="rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-700 ring-1 ring-inset ring-violet-600/20">Kalibrasi</span>
                                                <span x-show="!u.is_borrowed && !u.needs_calibration" class="text-xs text-zinc-400">Tersedia</span>
                                            </div>
                                        </td>
                                        <td class="table-td text-right">
                                            <template x-if="$store.auth.isAny(['laboran', 'admin_sistem'])">
                                                <button type="button" class="btn btn-ghost btn-sm" title="Ubah" @click="openUnitEdit(u)">
                                                    <x-icon name="pencil" class="h-3.5 w-3.5" />
                                                </button>
                                            </template>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        {{-- Movements --}}
                        <table x-show="tab === 'movements'" class="w-full min-w-[640px]">
                            <thead class="bg-zinc-50/70">
                                <tr>
                                    <th class="table-th">Jenis</th>
                                    <th class="table-th">Qty</th>
                                    <th class="table-th">Pelaku</th>
                                    <th class="table-th">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                <template x-for="m in tabItems" :key="m.id">
                                    <tr class="transition hover:bg-zinc-50/60">
                                        <td class="table-td">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(m.type)">
                                                <span x-text="fmt.movementLabel(m.type)"></span>
                                            </span>
                                        </td>
                                        <td class="table-td">
                                            <p class="font-mono font-medium" :class="m.type.startsWith('out') ? 'text-rose-600' : 'text-emerald-600'" x-text="`${m.type.startsWith('out') ? '−' : '+'}${fmt.fmtNum(m.quantity)}`"></p>
                                        </td>
                                        <td class="table-td text-sm text-zinc-600" x-text="m.performed_by?.name ?? '—'"></td>
                                        <td class="table-td text-sm text-zinc-500" x-text="fmt.fmtDateTime(m.occurred_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        {{-- Usages --}}
                        <table x-show="tab === 'usages'" class="w-full min-w-[640px]">
                            <thead class="bg-zinc-50/70">
                                <tr>
                                    <th class="table-th">Qty</th>
                                    <th class="table-th">Pemakai</th>
                                    <th class="table-th">Tujuan</th>
                                    <th class="table-th">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                <template x-for="u in tabItems" :key="u.id">
                                    <tr class="transition hover:bg-zinc-50/60">
                                        <td class="table-td">
                                            <p class="font-medium text-zinc-800" x-text="fmt.fmtNum(u.quantity_used)"></p>
                                        </td>
                                        <td class="table-td text-sm text-zinc-600" x-text="u.user?.name ?? '—'"></td>
                                        <td class="table-td text-sm text-zinc-500" x-text="u.purpose ?? '—'"></td>
                                        <td class="table-td">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(u.status)">
                                                <span x-text="fmt.statusLabel(u.status)"></span>
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        {{-- Calibrations --}}
                        <table x-show="tab === 'calibrations'" class="w-full min-w-[640px]">
                            <thead class="bg-zinc-50/70">
                                <tr>
                                    <th class="table-th">Tanggal</th>
                                    <th class="table-th">Hasil</th>
                                    <th class="table-th">Petugas</th>
                                    <th class="table-th">Sertifikat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                <template x-for="c in tabItems" :key="c.id">
                                    <tr class="transition hover:bg-zinc-50/60">
                                        <td class="table-td text-sm text-zinc-600" x-text="fmt.fmtDate(c.calibration_date)"></td>
                                        <td class="table-td">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(c.result)">
                                                <span x-text="c.result"></span>
                                            </span>
                                        </td>
                                        <td class="table-td text-sm text-zinc-600" x-text="c.calibrated_by ?? '—'"></td>
                                        <td class="table-td font-mono text-xs text-zinc-500" x-text="c.certificate_number ?? '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        {{-- Maintenances --}}
                        <table x-show="tab === 'maintenances'" class="w-full min-w-[640px]">
                            <thead class="bg-zinc-50/70">
                                <tr>
                                    <th class="table-th">Tanggal</th>
                                    <th class="table-th">Deskripsi</th>
                                    <th class="table-th">Status</th>
                                    <th class="table-th">Biaya</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                <template x-for="m in tabItems" :key="m.id">
                                    <tr class="transition hover:bg-zinc-50/60">
                                        <td class="table-td text-sm text-zinc-600" x-text="fmt.fmtDate(m.maintenance_date)"></td>
                                        <td class="table-td text-sm text-zinc-600" x-text="m.description ?? '—'"></td>
                                        <td class="table-td">
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(m.status)">
                                                <span x-text="fmt.statusLabel(m.status)"></span>
                                            </span>
                                        </td>
                                        <td class="table-td text-sm font-medium text-zinc-700" x-text="m.cost ? fmt.fmtRupiah(m.cost) : '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        {{-- Audit --}}
                        <table x-show="tab === 'audit'" class="w-full min-w-[640px]">
                            <thead class="bg-zinc-50/70">
                                <tr>
                                    <th class="table-th">Aksi</th>
                                    <th class="table-th">Entitas</th>
                                    <th class="table-th">User</th>
                                    <th class="table-th">Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                <template x-for="a in tabItems" :key="a.id">
                                    <tr class="transition hover:bg-zinc-50/60">
                                        <td class="table-td">
                                            <span class="inline-flex rounded-lg bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600" x-text="a.action"></span>
                                        </td>
                                        <td class="table-td text-sm text-zinc-600" x-text="fmt.modelName(a.auditable_type)"></td>
                                        <td class="table-td text-sm text-zinc-600" x-text="a.user?.name ?? '—'"></td>
                                        <td class="table-td text-sm text-zinc-500" x-text="fmt.fmtDateTime(a.created_at)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div x-show="tabLoading" class="flex items-center justify-center py-10">
                        <div class="h-7 w-7 animate-spin rounded-full border-2 border-zinc-200 border-t-brand-600"></div>
                    </div>

                    <div x-show="!tabLoading && !tabItems.length" x-cloak>
                        <x-empty-state title="Tidak ada data" subtitle="Belum ada riwayat pada tab ini." icon="file" />
                    </div>

                    <div x-show="tabMeta?.last_page > 1" x-cloak class="flex items-center justify-between border-t border-zinc-100 px-4 py-3">
                        <p class="text-xs text-zinc-400" x-text="`Menampilkan ${tabMeta.from ?? 0}–${tabMeta.to ?? 0} dari ${tabMeta.total ?? 0}`"></p>
                        <div class="flex items-center gap-1">
                            <button type="button" class="btn btn-secondary btn-sm" :disabled="tabMeta.current_page <= 1" @click="setTabPage(tabMeta.current_page - 1)">
                                <x-icon name="chevron-left" class="h-3.5 w-3.5" />
                            </button>
                            <template x-for="p in tabPageNumbers()" :key="p">
                                <button type="button" class="btn btn-sm" :class="p === tabMeta.current_page ? 'bg-brand-600 text-white' : 'btn-secondary'" @click="setTabPage(p)" x-text="p"></button>
                            </template>
                            <button type="button" class="btn btn-secondary btn-sm" :disabled="tabMeta.current_page >= tabMeta.last_page" @click="setTabPage(tabMeta.current_page + 1)">
                                <x-icon name="chevron-right" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Tambah Unit modal (alat) --}}
    <x-modal open="unitFormOpen" title="Tambah Unit" subtitle="Tambah unit fisik untuk item ini." maxWidth="max-w-2xl">
        <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="saveUnit()">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Item (alat)</label>
                <input type="text" class="input bg-zinc-50" :value="`${item?.name} (${item?.code})`" disabled />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Serial number</label>
                <input type="text" x-model="unitForm.serial_number" :class="unitErrors.serial_number ? 'input input-error' : 'input'" placeholder="SN-2026-0001" />
                <p x-show="unitErrors.serial_number" class="mt-1 text-xs text-rose-600" x-text="unitErrors.serial_number?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Asset tag</label>
                <input type="text" x-model="unitForm.asset_tag" :class="unitErrors.asset_tag ? 'input input-error' : 'input'" placeholder="AT-0001" />
                <p x-show="unitErrors.asset_tag" class="mt-1 text-xs text-rose-600" x-text="unitErrors.asset_tag?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kondisi</label>
                <select x-model="unitForm.condition" :class="unitErrors.condition ? 'input input-error' : 'input'">
                    <option value="baik">Baik</option>
                    <option value="rusak_ringan">Rusak ringan</option>
                    <option value="rusak_berat">Rusak berat</option>
                    <option value="hilang">Hilang</option>
                    <option value="dihapus">Dihapus</option>
                </select>
                <p x-show="unitErrors.condition" class="mt-1 text-xs text-rose-600" x-text="unitErrors.condition?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Lokasi</label>
                <select x-model="unitForm.location_id" :class="unitErrors.location_id ? 'input input-error' : 'input'">
                    <option value="">— Tanpa lokasi —</option>
                    <template x-for="loc in locations" :key="loc.id">
                        <option :value="loc.id" x-text="loc.name"></option>
                    </template>
                </select>
                <p x-show="unitErrors.location_id" class="mt-1 text-xs text-rose-600" x-text="unitErrors.location_id?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Tanggal pembelian</label>
                <input type="date" x-model="unitForm.purchase_date" :class="unitErrors.purchase_date ? 'input input-error' : 'input'" />
                <p x-show="unitErrors.purchase_date" class="mt-1 text-xs text-rose-600" x-text="unitErrors.purchase_date?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kalibrasi terakhir</label>
                <input type="date" x-model="unitForm.last_calibration_date" :class="unitErrors.last_calibration_date ? 'input input-error' : 'input'" />
                <p x-show="unitErrors.last_calibration_date" class="mt-1 text-xs text-rose-600" x-text="unitErrors.last_calibration_date?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kalibrasi berikutnya</label>
                <input type="date" x-model="unitForm.next_calibration_date" :class="unitErrors.next_calibration_date ? 'input input-error' : 'input'" />
                <p x-show="unitErrors.next_calibration_date" class="mt-1 text-xs text-rose-600" x-text="unitErrors.next_calibration_date?.[0]"></p>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Catatan</label>
                <textarea x-model="unitForm.notes" rows="3" :class="unitErrors.notes ? 'input input-error' : 'input'" placeholder="Catatan unit..."></textarea>
                <p x-show="unitErrors.notes" class="mt-1 text-xs text-rose-600" x-text="unitErrors.notes?.[0]"></p>
            </div>
            <div class="flex justify-end gap-2 sm:col-span-2">
                <button type="button" class="btn btn-secondary" @click="unitFormOpen = false">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="unitSaving">
                    <span x-show="unitSaving" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <span>Tambah Unit</span>
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Edit Unit modal (alat) --}}
    <x-modal open="unitEditOpen" title="Ubah Unit" subtitle="Perbarui kondisi, lokasi, dan catatan unit." maxWidth="max-w-2xl">
        <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="saveUnitEdit()">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Serial number</label>
                <input type="text" class="input bg-zinc-50" :value="tabItems.find(u => u.id === editUnitId)?.serial_number || tabItems.find(u => u.id === editUnitId)?.asset_tag || '—'" disabled />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kondisi</label>
                <select x-model="unitEditForm.condition" :class="unitEditErrors.condition ? 'input input-error' : 'input'">
                    <option value="baik">Baik</option>
                    <option value="rusak_ringan">Rusak ringan</option>
                    <option value="rusak_berat">Rusak berat</option>
                    <option value="hilang">Hilang</option>
                    <option value="dihapus">Dihapus</option>
                </select>
                <p x-show="unitEditErrors.condition" class="mt-1 text-xs text-rose-600" x-text="unitEditErrors.condition?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Lokasi</label>
                <select x-model="unitEditForm.location_id" :class="unitEditErrors.location_id ? 'input input-error' : 'input'">
                    <option value="">— Tanpa lokasi —</option>
                    <template x-for="loc in locations" :key="loc.id">
                        <option :value="loc.id" x-text="loc.name"></option>
                    </template>
                </select>
                <p x-show="unitEditErrors.location_id" class="mt-1 text-xs text-rose-600" x-text="unitEditErrors.location_id?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kalibrasi terakhir</label>
                <input type="date" x-model="unitEditForm.last_calibration_date" :class="unitEditErrors.last_calibration_date ? 'input input-error' : 'input'" />
                <p x-show="unitEditErrors.last_calibration_date" class="mt-1 text-xs text-rose-600" x-text="unitEditErrors.last_calibration_date?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kalibrasi berikutnya</label>
                <input type="date" x-model="unitEditForm.next_calibration_date" :class="unitEditErrors.next_calibration_date ? 'input input-error' : 'input'" />
                <p x-show="unitEditErrors.next_calibration_date" class="mt-1 text-xs text-rose-600" x-text="unitEditErrors.next_calibration_date?.[0]"></p>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Catatan</label>
                <textarea x-model="unitEditForm.notes" rows="3" :class="unitEditErrors.notes ? 'input input-error' : 'input'" placeholder="Catatan unit..."></textarea>
                <p x-show="unitEditErrors.notes" class="mt-1 text-xs text-rose-600" x-text="unitEditErrors.notes?.[0]"></p>
            </div>
            <div class="flex justify-end gap-2 sm:col-span-2">
                <button type="button" class="btn btn-secondary" @click="unitEditOpen = false">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="unitEditSaving">
                    <span x-show="unitEditSaving" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
