@extends('layouts.app')

@section('title', 'Pemakaian')

@section('content')
<div x-data="usagesPage">
    <x-page-header title="Pemakaian" subtitle="Catatan pemakaian bahan dan alat laboratorium.">
        <x-slot:actions>
            <button type="button" @click="openCreate()" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Catat Pemakaian
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="card mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 p-4">
            <select x-model="filters.status" @change="applyFilters()" class="input !w-auto">
                <option value="">Semua status</option>
                <option value="dicatat">Dicatat</option>
                <option value="diverifikasi">Diverifikasi</option>
                <option value="ditolak">Ditolak</option>
            </select>
            <select x-model="filters.item_id" @change="applyFilters()" class="input !w-auto">
                <option value="">Semua item</option>
                <template x-for="item in itemOptions" :key="item.id">
                    <option :value="item.id" x-text="item.name"></option>
                </template>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="bg-zinc-50/70">
                    <tr>
                        <th class="table-th">Item</th>
                        <th class="table-th">Qty</th>
                        <th class="table-th">Pemakai</th>
                        <th class="table-th">Tujuan</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="u in items" :key="u.id">
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="table-td">
                                <a :href="`/items/${u.item?.id}`" class="font-semibold text-zinc-900 transition hover:text-brand-600" x-text="u.item?.name ?? '—'"></a>
                                <p class="mt-0.5 text-xs text-zinc-400" x-text="u.item?.code"></p>
                            </td>
                            <td class="table-td">
                                <p class="font-medium text-zinc-800" x-text="fmt.fmtNum(u.quantity_used)"></p>
                            </td>
                            <td class="table-td text-sm text-zinc-600" x-text="u.user?.name ?? '—'"></td>
                            <td class="table-td max-w-[200px] truncate text-sm text-zinc-500" x-text="u.purpose ?? '—'"></td>
                            <td class="table-td">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(u.status)">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-60"></span>
                                    <span x-text="fmt.statusLabel(u.status)"></span>
                                </span>
                            </td>
                            <td class="table-td text-right">
                                <div class="inline-flex items-center gap-1">
                                    <template x-if="u.status === 'dicatat' && $store.auth.isAny(['laboran'])">
                                        <button type="button" class="btn btn-ghost btn-sm !text-emerald-600 hover:!bg-emerald-50" title="Verifikasi" @click="verify(u)">
                                            <x-icon name="check" class="h-3.5 w-3.5" />
                                        </button>
                                    </template>
                                    <template x-if="u.status === 'dicatat' && $store.auth.isAny(['laboran'])">
                                        <button type="button" class="btn btn-ghost btn-sm !text-rose-500 hover:!bg-rose-50" title="Tolak" @click="openReject(u)">
                                            <x-icon name="x" class="h-3.5 w-3.5" />
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
                <x-empty-state title="Belum ada pemakaian" subtitle="Belum ada catatan pemakaian yang cocok dengan filter." icon="flask" />
            </div>
        </div>

        <x-pagination />
    </div>

    {{-- Create modal --}}
    <x-modal open="formOpen" title="Catat Pemakaian" subtitle="Catat pemakaian item laboratorium.">
        <form class="grid grid-cols-1 gap-4" @submit.prevent="save()">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Item</label>
                <select x-model="form.item_id" @change="onItemChange()" :class="errors.item_id ? 'input input-error' : 'input'">
                    <option value="">— Pilih item —</option>
                    <template x-for="item in itemOptions" :key="item.id">
                        <option :value="item.id" x-text="`${item.name} (stok ${fmt.fmtNum(item.stock_quantity)})`"></option>
                    </template>
                </select>
                <p x-show="errors.item_id" class="mt-1 text-xs text-rose-600" x-text="errors.item_id?.[0]"></p>
            </div>
            <div x-show="units.length">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Unit fisik (opsional)</label>
                <select x-model="form.item_unit_id" class="input">
                    <option value="">— Tanpa unit —</option>
                    <template x-for="unit in units" :key="unit.id">
                        <option :value="unit.id" x-text="unit.serial_number ?? unit.asset_tag ?? unit.id"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Jumlah</label>
                <input type="number" min="0.5" step="0.5" x-model="form.quantity_used" :class="errors.quantity_used ? 'input input-error' : 'input'" placeholder="0" />
                <p x-show="errors.quantity_used" class="mt-1 text-xs text-rose-600" x-text="errors.quantity_used?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Tujuan</label>
                <input type="text" x-model="form.purpose" :class="errors.purpose ? 'input input-error' : 'input'" placeholder="Praktikum, riset..." />
                <p x-show="errors.purpose" class="mt-1 text-xs text-rose-600" x-text="errors.purpose?.[0]"></p>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="formOpen = false">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Reject modal --}}
    <x-modal open="rejectOpen" title="Tolak Pemakaian" subtitle="Sampaikan alasan penolakan catatan pemakaian.">
        <div class="grid gap-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Alasan penolakan</label>
                <textarea x-model="rejectionReason" rows="3" class="input" placeholder="Alasan penolakan..."></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="rejectOpen = false">Batal</button>
                <button type="button" class="btn btn-danger" :disabled="!rejectionReason.trim()" @click="reject()">Tolak</button>
            </div>
        </div>
    </x-modal>
</div>
@endsection
