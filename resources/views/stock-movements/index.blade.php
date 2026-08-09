@extends('layouts.app')

@section('title', 'Mutasi Stok')

@section('content')
<div x-data="stockMovementsPage">
    <x-page-header title="Mutasi Stok" subtitle="Ledger mutasi stok masuk dan keluar (append-only).">
        <x-slot:actions>
            <button type="button" x-show="$store.auth.isAny(['laboran', 'admin_sistem'])" @click="openCreate()" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Catat Mutasi
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="card mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 p-4">
            <select x-model="filters.type" @change="applyFilters()" class="input !w-auto">
                <option value="">Semua jenis</option>
                <option value="in_purchase">In · Pembelian</option>
                <option value="in_return">In · Pengembalian</option>
                <option value="in_adjustment">In · Penyesuaian</option>
                <option value="transfer_in">In · Transfer</option>
                <option value="out_borrow">Out · Peminjaman</option>
                <option value="out_usage">Out · Pemakaian</option>
                <option value="out_disposal">Out · Disposal</option>
                <option value="out_adjustment">Out · Penyesuaian</option>
                <option value="transfer_out">Out · Transfer</option>
            </select>
            <select x-model="filters.item_id" @change="applyFilters()" class="input !w-auto">
                <option value="">Semua item</option>
                <template x-for="item in itemOptions" :key="item.id">
                    <option :value="item.id" x-text="item.name"></option>
                </template>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px]">
                <thead class="bg-zinc-50/70">
                    <tr>
                        <th class="table-th">Item</th>
                        <th class="table-th">Jenis</th>
                        <th class="table-th">Qty</th>
                        <th class="table-th">Saldo</th>
                        <th class="table-th">Pelaku</th>
                        <th class="table-th">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="m in items" :key="m.id">
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="table-td">
                                <a :href="`/items/${m.item?.id}`" class="font-semibold text-zinc-900 transition hover:text-brand-600" x-text="m.item?.name ?? '—'"></a>
                                <p class="mt-0.5 text-xs text-zinc-400" x-text="m.item?.code"></p>
                            </td>
                            <td class="table-td">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(m.type)">
                                    <span x-text="fmt.movementLabel(m.type)"></span>
                                </span>
                            </td>
                            <td class="table-td">
                                <p class="font-mono font-medium" :class="m.type.startsWith('out') ? 'text-rose-600' : 'text-emerald-600'" x-text="`${m.type.startsWith('out') ? '−' : '+'}${fmt.fmtNum(m.quantity)}`"></p>
                            </td>
                            <td class="table-td font-mono text-xs text-zinc-500" x-text="`${fmt.fmtNum(m.quantity_before)} → ${fmt.fmtNum(m.quantity_after)}`"></td>
                            <td class="table-td text-sm text-zinc-600" x-text="m.performed_by?.name ?? '—'"></td>
                            <td class="table-td text-sm text-zinc-500" x-text="fmt.fmtDateTime(m.occurred_at)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div x-show="loading" class="flex items-center justify-center py-16">
                <div class="h-8 w-8 animate-spin rounded-full border-2 border-zinc-200 border-t-brand-600"></div>
            </div>

            <div x-show="!loading && !items.length" x-cloak>
                <x-empty-state title="Belum ada mutasi" subtitle="Belum ada mutasi stok yang cocok dengan filter." icon="arrows-right-left" />
            </div>
        </div>

        <x-pagination />
    </div>

    <x-modal open="formOpen" title="Catat Mutasi Stok" subtitle="Masuk atau keluarkan stok dari katalog.">
        <form class="grid grid-cols-1 gap-4 sm:grid-cols-2" @submit.prevent="save()">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Item</label>
                <select x-model="form.item_id" @change="onItemChange()" :class="errors.item_id ? 'input input-error' : 'input'">
                    <option value="">— Pilih item —</option>
                    <template x-for="item in itemOptions" :key="item.id">
                        <option :value="item.id" x-text="`${item.name} (stok ${fmt.fmtNum(item.stock_quantity)})`"></option>
                    </template>
                </select>
                <p x-show="errors.item_id" class="mt-1 text-xs text-rose-600" x-text="errors.item_id?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Jenis mutasi</label>
                <select x-model="form.type" :class="errors.type ? 'input input-error' : 'input'">
                    <option value="in_purchase">Masuk · Pembelian</option>
                    <option value="in_return">Masuk · Pengembalian</option>
                    <option value="in_adjustment">Masuk · Penyesuaian</option>
                    <option value="transfer_in">Masuk · Transfer</option>
                    <option value="out_borrow">Keluar · Peminjaman</option>
                    <option value="out_usage">Keluar · Pemakaian</option>
                    <option value="out_disposal">Keluar · Disposal</option>
                    <option value="out_adjustment">Keluar · Penyesuaian</option>
                    <option value="transfer_out">Keluar · Transfer</option>
                </select>
                <p x-show="errors.type" class="mt-1 text-xs text-rose-600" x-text="errors.type?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Jumlah</label>
                <input type="number" min="0.5" step="0.5" x-model="form.quantity" :class="errors.quantity ? 'input input-error' : 'input'" placeholder="0" />
                <p x-show="errors.quantity" class="mt-1 text-xs text-rose-600" x-text="errors.quantity?.[0]"></p>
            </div>
            <div x-show="units.length" class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Unit fisik (opsional)</label>
                <select x-model="form.item_unit_id" class="input">
                    <option value="">— Tanpa unit —</option>
                    <template x-for="unit in units" :key="unit.id">
                        <option :value="unit.id" x-text="unit.serial_number ?? unit.asset_tag ?? unit.id"></option>
                    </template>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Catatan</label>
                <textarea x-model="form.notes" rows="3" :class="errors.notes ? 'input input-error' : 'input'" placeholder="Catatan mutasi..."></textarea>
                <p x-show="errors.notes" class="mt-1 text-xs text-rose-600" x-text="errors.notes?.[0]"></p>
            </div>
            <div class="flex justify-end gap-2 sm:col-span-2">
                <button type="button" class="btn btn-secondary" @click="formOpen = false">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
