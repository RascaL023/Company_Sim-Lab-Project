@extends('layouts.app')

@section('title', 'Perawatan')

<div x-data="maintenancesPage">
    <x-page-header title="Perawatan" subtitle="Riwayat maintenance unit alat laboratorium.">
        <x-slot:actions>
            <button type="button" x-show="auth.isAny(['laboran', 'admin_sistem'])" @click="openCreate()" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Catat Perawatan
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="card mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 p-4">
            <select x-model="filters.status" @change="applyFilters()" class="input !w-auto">
                <option value="">Semua status</option>
                <option value="proses">Proses</option>
                <option value="tertunda">Tertunda</option>
                <option value="selesai">Selesai</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="bg-zinc-50/70">
                    <tr>
                        <th class="table-th">Unit</th>
                        <th class="table-th">Deskripsi</th>
                        <th class="table-th">Tanggal</th>
                        <th class="table-th">Biaya</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="m in items" :key="m.id">
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="table-td">
                                <a :href="`/items/${m.item_unit?.item?.id}`" class="font-semibold text-zinc-900 transition hover:text-brand-600" x-text="m.item_unit?.item?.name ?? '—'"></a>
                                <p class="mt-0.5 font-mono text-xs text-zinc-400" x-text="m.item_unit?.serial_number ?? m.item_unit?.asset_tag ?? ''"></p>
                            </td>
                            <td class="table-td max-w-[240px] truncate text-sm text-zinc-600" x-text="m.description ?? '—'"></td>
                            <td class="table-td text-sm text-zinc-600" x-text="fmt.fmtDate(m.maintenance_date)"></td>
                            <td class="table-td text-sm font-medium text-zinc-700" x-text="m.cost ? fmt.fmtRupiah(m.cost) : '—'"></td>
                            <td class="table-td">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(m.status)">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-60"></span>
                                    <span x-text="fmt.statusLabel(m.status)"></span>
                                </span>
                            </td>
                            <td class="table-td text-right">
                                <div class="inline-flex items-center gap-1">
                                    <template x-if="auth.isAny(['laboran', 'admin_sistem'])">
                                        <button type="button" class="btn btn-ghost btn-sm" title="Ubah" @click="openEdit(m)">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
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
                <x-empty-state title="Belum ada perawatan" subtitle="Belum ada maintenance yang tercatat." icon="wrench" />
            </div>
        </div>

        <x-pagination />
    </div>

    <x-modal open="formOpen" title="Catat Perawatan" title-expr="editId ? 'Ubah Perawatan' : 'Catat Perawatan'" subtitle="Catat maintenance unit alat." subtitle-expr="editId ? 'Perbarui data maintenance.' : 'Catat maintenance unit alat.'" maxWidth="max-w-2xl">
        <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="save()">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Unit alat</label>
                <select x-model="form.item_unit_id" :class="errors.item_unit_id ? 'input input-error' : 'input'">
                    <option value="">— Pilih unit —</option>
                    <template x-for="unit in units" :key="unit.id">
                        <option :value="unit.id" x-text="`${unit.item?.name ?? unit.serial_number ?? unit.id} — ${unit.serial_number ?? unit.asset_tag ?? unit.condition}`"></option>
                    </template>
                </select>
                <p x-show="errors.item_unit_id" class="mt-1 text-xs text-rose-600" x-text="errors.item_unit_id?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Tanggal maintenance</label>
                <input type="date" x-model="form.maintenance_date" :class="errors.maintenance_date ? 'input input-error' : 'input'" />
                <p x-show="errors.maintenance_date" class="mt-1 text-xs text-rose-600" x-text="errors.maintenance_date?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Status</label>
                <select x-model="form.status" :class="errors.status ? 'input input-error' : 'input'">
                    <option value="proses">Proses</option>
                    <option value="tertunda">Tertunda</option>
                    <option value="selesai">Selesai</option>
                </select>
                <p x-show="errors.status" class="mt-1 text-xs text-rose-600" x-text="errors.status?.[0]"></p>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Deskripsi</label>
                <input type="text" x-model="form.description" :class="errors.description ? 'input input-error' : 'input'" placeholder="Jenis perawatan" />
                <p x-show="errors.description" class="mt-1 text-xs text-rose-600" x-text="errors.description?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Petugas</label>
                <input type="text" x-model="form.performed_by" :class="errors.performed_by ? 'input input-error' : 'input'" placeholder="Nama petugas / vendor" />
                <p x-show="errors.performed_by" class="mt-1 text-xs text-rose-600" x-text="errors.performed_by?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Biaya (Rp)</label>
                <input type="number" min="0" step="1000" x-model="form.cost" :class="errors.cost ? 'input input-error' : 'input'" placeholder="0" />
                <p x-show="errors.cost" class="mt-1 text-xs text-rose-600" x-text="errors.cost?.[0]"></p>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Catatan</label>
                <textarea x-model="form.notes" rows="3" :class="errors.notes ? 'input input-error' : 'input'" placeholder="Catatan maintenance..."></textarea>
                <p x-show="errors.notes" class="mt-1 text-xs text-rose-600" x-text="errors.notes?.[0]"></p>
            </div>
            <div class="flex justify-end gap-2 sm:col-span-2">
                <button type="button" class="btn btn-secondary" @click="formOpen = false">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <span x-text="editId ? 'Simpan Perubahan' : 'Simpan'"></span>
                </button>
            </div>
        </form>
    </x-modal>
</div>
