@extends('layouts.app')

@section('title', 'Unit Item')

<div x-data="itemUnitsPage">
    <x-page-header title="Unit Item" subtitle="Unit fisik alat/bahan: serial, kondisi, dan lokasi." />

    <div class="card mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 p-4">
            <select x-model="filters.condition" @change="applyFilters()" class="input !w-auto">
                <option value="">Semua kondisi</option>
                <option value="baik">Baik</option>
                <option value="rusak_ringan">Rusak ringan</option>
                <option value="rusak_berat">Rusak berat</option>
                <option value="hilang">Hilang</option>
            </select>
            <select x-model="filters.location_id" @change="applyFilters()" class="input !w-auto">
                <option value="">Semua lokasi</option>
                <template x-for="loc in locations" :key="loc.id">
                    <option :value="loc.id" x-text="loc.name"></option>
                </template>
            </select>
            <button
                type="button"
                class="btn btn-sm"
                :class="filters.needs_calibration ? 'bg-violet-100 text-violet-700 ring-1 ring-inset ring-violet-600/20' : 'btn-secondary'"
                @click="filters.needs_calibration = filters.needs_calibration ? '' : true; applyFilters()"
            >Perlu kalibrasi</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="bg-zinc-50/70">
                    <tr>
                        <th class="table-th">Item</th>
                        <th class="table-th">Unit</th>
                        <th class="table-th">Kondisi</th>
                        <th class="table-th">Lokasi</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="unit in items" :key="unit.id">
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="table-td">
                                <a :href="`/items/${unit.item?.id}`" class="font-semibold text-zinc-900 transition hover:text-brand-600" x-text="unit.item?.name ?? '—'"></a>
                                <p class="mt-0.5 text-xs text-zinc-400" x-text="unit.item?.code"></p>
                            </td>
                            <td class="table-td">
                                <p class="font-mono text-sm font-medium text-zinc-700" x-text="unit.serial_number ?? unit.asset_tag ?? '—'"></p>
                                <p x-show="unit.serial_number && unit.asset_tag" class="mt-0.5 text-xs text-zinc-400" x-text="unit.asset_tag"></p>
                            </td>
                            <td class="table-td">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(unit.condition)">
                                    <span x-text="fmt.conditionLabel(unit.condition)"></span>
                                </span>
                            </td>
                            <td class="table-td text-sm text-zinc-600" x-text="unit.location?.name ?? '—'"></td>
                            <td class="table-td">
                                <div class="flex flex-wrap gap-1">
                                    <span x-show="unit.needs_calibration" class="rounded-full bg-violet-50 px-2 py-0.5 text-[11px] font-medium text-violet-700 ring-1 ring-inset ring-violet-600/20">Kalibrasi</span>
                                    <span x-show="unit.is_expired" class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20">Expired</span>
                                    <span x-show="!unit.needs_calibration && !unit.is_expired" class="text-xs text-zinc-400">Normal</span>
                                </div>
                            </td>
                            <td class="table-td text-right">
                                <div class="inline-flex items-center gap-1">
                                    <template x-if="auth.isAny(['laboran', 'admin_sistem'])">
                                        <button type="button" class="btn btn-ghost btn-sm" title="Ubah" @click="openEdit(unit)">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        </button>
                                    </template>
                                    <template x-if="auth.isAny(['laboran', 'admin_sistem'])">
                                        <button type="button" class="btn btn-ghost btn-sm !text-rose-500 hover:!bg-rose-50" title="Hapus" @click="remove(unit)">
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
                <x-empty-state title="Belum ada unit item" subtitle="Belum ada unit yang cocok dengan filter saat ini." icon="layers" />
            </div>
        </div>

        <x-pagination />
    </div>

    <x-modal open="formOpen" title="Ubah Unit" subtitle="Perbarui kondisi dan lokasi unit." subtitle-expr="editId ? 'Perbarui kondisi dan lokasi unit.' : ''">
        <form class="grid grid-cols-1 gap-4" @submit.prevent="save()">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kondisi</label>
                <select x-model="form.condition" :class="errors.condition ? 'input input-error' : 'input'">
                    <option value="baik">Baik</option>
                    <option value="rusak_ringan">Rusak ringan</option>
                    <option value="rusak_berat">Rusak berat</option>
                    <option value="hilang">Hilang</option>
                </select>
                <p x-show="errors.condition" class="mt-1 text-xs text-rose-600" x-text="errors.condition?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Lokasi</label>
                <select x-model="form.location_id" :class="errors.location_id ? 'input input-error' : 'input'">
                    <option value="">— Tanpa lokasi —</option>
                    <template x-for="loc in locations" :key="loc.id">
                        <option :value="loc.id" x-text="loc.name"></option>
                    </template>
                </select>
                <p x-show="errors.location_id" class="mt-1 text-xs text-rose-600" x-text="errors.location_id?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Catatan</label>
                <textarea x-model="form.notes" rows="3" :class="errors.notes ? 'input input-error' : 'input'" placeholder="Catatan unit..."></textarea>
                <p x-show="errors.notes" class="mt-1 text-xs text-rose-600" x-text="errors.notes?.[0]"></p>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="formOpen = false">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </x-modal>
</div>
