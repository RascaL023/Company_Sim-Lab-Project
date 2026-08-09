@extends('layouts.app')

@section('title', 'Items')

@section('content')
<div x-data="itemsPage">
    <x-page-header title="Items" subtitle="Katalog master alat dan bahan laboratorium.">
        <x-slot:actions>
            <button
                type="button"
                x-show="$store.auth.isAny(['laboran', 'admin_sistem'])"
                @click="openCreate()"
                class="btn btn-primary"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Tambah Item
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="card mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 p-4">
            <div class="relative min-w-0 flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                <input type="text" x-model="filters.search" @input.debounce.300ms="applyFilters()" class="input !pl-10" placeholder="Cari nama, kode, atau deskripsi..." />
            </div>
            <select x-model="filters.type" @change="applyFilters()" class="input !w-auto">
                <option value="">Semua tipe</option>
                <option value="alat">Alat</option>
                <option value="bahan">Bahan</option>
            </select>
            <button
                type="button"
                class="btn btn-sm"
                :class="filters.low_stock ? 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-600/20' : 'btn-secondary'"
                @click="filters.low_stock = filters.low_stock ? '' : true; applyFilters()"
            >Stok menipis</button>
            <button
                type="button"
                class="btn btn-sm"
                :class="filters.needs_calibration ? 'bg-violet-100 text-violet-700 ring-1 ring-inset ring-violet-600/20' : 'btn-secondary'"
                @click="filters.needs_calibration = filters.needs_calibration ? '' : true; applyFilters()"
            >Perlu kalibrasi</button>
            <button
                type="button"
                class="btn btn-sm"
                :class="filters.expired ? 'bg-rose-100 text-rose-700 ring-1 ring-inset ring-rose-600/20' : 'btn-secondary'"
                @click="filters.expired = filters.expired ? '' : true; applyFilters()"
            >Kedaluwarsa</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="bg-zinc-50/70">
                    <tr>
                        <th class="table-th">Kode</th>
                        <th class="table-th">Nama</th>
                        <th class="table-th">Tipe</th>
                        <th class="table-th">Stok</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="item in items" :key="item.id">
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="table-td">
                                <span class="inline-flex rounded-lg bg-zinc-100 px-2 py-0.5 font-mono text-xs font-medium text-zinc-600" x-text="item.code"></span>
                            </td>
                            <td class="table-td">
                                <a :href="`/items/${item.id}`" class="font-semibold text-zinc-900 transition hover:text-brand-600" x-text="item.name"></a>
                                <p class="mt-0.5 text-xs text-zinc-400" x-text="item.category?.name"></p>
                            </td>
                            <td class="table-td">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(item.type)">
                                    <span x-text="fmt.typeLabel(item.type)"></span>
                                </span>
                            </td>
                            <td class="table-td">
                                <p class="font-medium text-zinc-800" x-text="`${fmt.fmtNum(item.stock_quantity)} ${item.unit}`"></p>
                                <p class="text-xs text-zinc-400" x-text="`min. ${fmt.fmtNum(item.minimum_stock)}`"></p>
                            </td>
                            <td class="table-td">
                                <div class="flex flex-wrap gap-1">
                                    <span x-show="item.is_low_stock" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">Stok menipis</span>
                                    <span x-show="item.is_expired" class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">Kedaluwarsa</span>
                                    <span x-show="item.needs_calibration" class="inline-flex items-center gap-1 rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-700 ring-1 ring-inset ring-violet-600/20">Kalibrasi</span>
                                    <span x-show="!item.is_low_stock && !item.is_expired && !item.needs_calibration" class="text-xs text-zinc-400">Normal</span>
                                </div>
                            </td>
                            <td class="table-td text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a :href="`/items/${item.id}`" class="btn btn-ghost btn-sm" title="Detail">
                                        <x-icon name="eye" class="h-3.5 w-3.5" />
                                    </a>
                                    <template x-if="$store.auth.isAny(['laboran', 'admin_sistem'])">
                                        <button type="button" class="btn btn-ghost btn-sm" title="Ubah" @click="openEdit(item)">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        </button>
                                    </template>
                                    <template x-if="$store.auth.isAny(['laboran', 'admin_sistem'])">
                                        <button type="button" class="btn btn-ghost btn-sm !text-rose-500 hover:!bg-rose-50" title="Hapus" @click="remove(item)">
                                            <x-icon name="trash" class="h-3.5 w-3.5" />
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
                <x-empty-state title="Belum ada item" subtitle="Belum ada data item yang cocok dengan filter saat ini." icon="package" />
            </div>
        </div>

        <x-pagination />
    </div>

    {{-- Form modal --}}
    <x-modal open="formOpen" title="Tambah Item" title-expr="editId ? 'Ubah Item' : 'Tambah Item'" subtitle="Masukkan data item baru ke katalog." subtitle-expr="editId ? 'Perbarui data katalog item.' : 'Masukkan data item baru ke katalog.'" maxWidth="max-w-2xl">
        <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="save()">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kategori</label>
                <select x-model="form.category_id" @change="form.type = categories.find(c => c.id == form.category_id)?.type ?? form.type" :class="errors.category_id ? 'input input-error' : 'input'">
                    <option value="">— Pilih kategori —</option>
                    <template x-for="cat in categories" :key="cat.id">
                        <option :value="cat.id" x-text="`${cat.name} (${fmt.typeLabel(cat.type)})`"></option>
                    </template>
                </select>
                <p x-show="errors.category_id" class="mt-1 text-xs text-rose-600" x-text="errors.category_id?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kode</label>
                <input type="text" x-model="form.code" :class="errors.code ? 'input input-error' : 'input'" placeholder="MCS-002" />
                <p x-show="errors.code" class="mt-1 text-xs text-rose-600" x-text="errors.code?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Nama</label>
                <input type="text" x-model="form.name" :class="errors.name ? 'input input-error' : 'input'" placeholder="Nama item" />
                <p x-show="errors.name" class="mt-1 text-xs text-rose-600" x-text="errors.name?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Tipe</label>
                <select x-model="form.type" :class="errors.type ? 'input input-error' : 'input'">
                    <option value="alat">Alat</option>
                    <option value="bahan">Bahan</option>
                </select>
                <p x-show="errors.type" class="mt-1 text-xs text-rose-600" x-text="errors.type?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Satuan</label>
                <input type="text" x-model="form.unit" :class="errors.unit ? 'input input-error' : 'input'" placeholder="pcs, set, liter..." />
                <p x-show="errors.unit" class="mt-1 text-xs text-rose-600" x-text="errors.unit?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Stok</label>
                <input type="number" min="0" step="any" x-model="form.stock_quantity" :class="errors.stock_quantity ? 'input input-error' : 'input'" />
                <p x-show="errors.stock_quantity" class="mt-1 text-xs text-rose-600" x-text="errors.stock_quantity?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Stok Minimum</label>
                <input type="number" min="0" step="any" x-model="form.minimum_stock" :class="errors.minimum_stock ? 'input input-error' : 'input'" />
                <p x-show="errors.minimum_stock" class="mt-1 text-xs text-rose-600" x-text="errors.minimum_stock?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Lokasi (katalog)</label>
                <input type="text" x-model="form.location" :class="errors.location ? 'input input-error' : 'input'" placeholder="Ruang Lab 1" />
                <p x-show="errors.location" class="mt-1 text-xs text-rose-600" x-text="errors.location?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Manufacturer</label>
                <input type="text" x-model="form.manufacturer" :class="errors.manufacturer ? 'input input-error' : 'input'" placeholder="Nama pabrikan" />
                <p x-show="errors.manufacturer" class="mt-1 text-xs text-rose-600" x-text="errors.manufacturer?.[0]"></p>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Deskripsi</label>
                <textarea x-model="form.description" rows="3" :class="errors.description ? 'input input-error' : 'input'" placeholder="Deskripsi singkat item..."></textarea>
                <p x-show="errors.description" class="mt-1 text-xs text-rose-600" x-text="errors.description?.[0]"></p>
            </div>
            <div class="flex justify-end gap-2 sm:col-span-2">
                <button type="button" class="btn btn-secondary" @click="formOpen = false">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <span x-text="editId ? 'Simpan Perubahan' : 'Tambah Item'"></span>
                </button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
