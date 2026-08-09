@extends('layouts.app')

@section('title', 'Disposal')

<div x-data="disposalsPage" x-cloak>
    <x-page-header title="Disposal" subtitle="Ajukan penghapusan aset yang rusak total, kedaluwarsa, atau hilang." />

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h2 class="font-display text-base font-semibold text-zinc-900">Usulan Disposal</h2>
            </div>
            <div class="p-5">
                <div x-show="loading" class="flex items-center justify-center py-12">
                    <div class="h-7 w-7 animate-spin rounded-full border-2 border-zinc-200 border-t-brand-600"></div>
                </div>

                <div x-show="!loading" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700">Jenis aset</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" class="btn justify-center" :class="targetType === 'alat' ? 'bg-brand-600 text-white hover:bg-brand-700' : 'btn-secondary'" @click="targetType = 'alat'">Alat</button>
                            <button type="button" class="btn justify-center" :class="targetType === 'bahan' ? 'bg-brand-600 text-white hover:bg-brand-700' : 'btn-secondary'" @click="targetType = 'bahan'">Bahan</button>
                        </div>
                    </div>

                    <div x-show="targetType === 'alat'" class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700">Unit alat</label>
                        <select x-model="form.item_unit_id" :class="errors.item_unit_id ? 'input input-error' : 'input'">
                            <option value="">— Pilih unit —</option>
                            <template x-for="unit in units" :key="unit.id">
                                <option :value="unit.id" x-text="`${unit.item?.name ?? unit.serial_number ?? unit.id} — ${unit.serial_number ?? unit.asset_tag ?? unit.condition}`"></option>
                            </template>
                        </select>
                        <p x-show="errors.item_unit_id" class="mt-1 text-xs text-rose-600" x-text="errors.item_unit_id?.[0]"></p>
                    </div>

                    <div x-show="targetType === 'bahan'" class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700">Item bahan</label>
                        <select x-model="form.item_id" :class="errors.item_id ? 'input input-error' : 'input'">
                            <option value="">— Pilih item —</option>
                            <template x-for="item in items" :key="item.id">
                                <option :value="item.id" x-text="`${item.name} (stok ${fmt.fmtNum(item.stock_quantity)})`"></option>
                            </template>
                        </select>
                        <p x-show="errors.item_id" class="mt-1 text-xs text-rose-600" x-text="errors.item_id?.[0]"></p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700">Alasan</label>
                        <select x-model="form.reason" :class="errors.reason ? 'input input-error' : 'input'">
                            <option value="rusak_total">Rusak total</option>
                            <option value="kedaluwarsa">Kedaluwarsa</option>
                            <option value="hilang">Hilang</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                        <p x-show="errors.reason" class="mt-1 text-xs text-rose-600" x-text="errors.reason?.[0]"></p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700">Catatan</label>
                        <textarea x-model="form.notes" rows="3" :class="errors.notes ? 'input input-error' : 'input'" placeholder="Detail usulan disposal..."></textarea>
                        <p x-show="errors.notes" class="mt-1 text-xs text-rose-600" x-text="errors.notes?.[0]"></p>
                    </div>

                    <div class="flex justify-end gap-2 sm:col-span-2">
                        <button type="button" class="btn btn-primary" :disabled="submitting" @click="submit()">
                            <span x-show="submitting" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                            <span>Ajukan Disposal</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card h-fit">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h2 class="font-display text-base font-semibold text-zinc-900">Alur Persetujuan</h2>
            </div>
            <div class="space-y-5 p-5">
                <div class="flex gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-sm font-bold text-zinc-500">1</div>
                    <div>
                        <p class="text-sm font-semibold text-zinc-800">Pengajuan</p>
                        <p class="mt-0.5 text-xs text-zinc-500">Usulan dicatat dengan status <span class="font-medium">diusulkan</span>.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-sm font-bold text-zinc-500">2</div>
                    <div>
                        <p class="text-sm font-semibold text-zinc-800">Persetujuan</p>
                        <p class="mt-0.5 text-xs text-zinc-500">Disetujui atau ditolak oleh pihak yang berwenang.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-sm font-bold text-zinc-500">3</div>
                    <div>
                        <p class="text-sm font-semibold text-zinc-800">Eksekusi</p>
                        <p class="mt-0.5 text-xs text-zinc-500">Jika disetujui, stok/unit dicatat keluar sebagai mutasi <span class="font-medium">out_disposal</span>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
