@extends('layouts.app')

@section('title', 'Kalibrasi')

@section('content')
<div x-data="calibrationsPage">
    <x-page-header title="Kalibrasi" subtitle="Riwayat kalibrasi unit alat laboratorium.">
        <x-slot:actions>
            <button type="button" x-show="$store.auth.isAny(['laboran', 'admin_sistem'])" @click="openCreate()" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Catat Kalibrasi
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="card mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 p-4">
            <button
                type="button"
                class="btn btn-sm"
                :class="filters.needs_calibration ? 'bg-violet-100 text-violet-700 ring-1 ring-inset ring-violet-600/20' : 'btn-secondary'"
                @click="filters.needs_calibration = filters.needs_calibration ? '' : true; applyFilters()"
            >Unit perlu kalibrasi</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="bg-zinc-50/70">
                    <tr>
                        <th class="table-th">Unit</th>
                        <th class="table-th">Tanggal</th>
                        <th class="table-th">Kalibrasi berikut</th>
                        <th class="table-th">Hasil</th>
                        <th class="table-th">Petugas</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="c in items" :key="c.id">
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="table-td">
                                <a :href="`/items/${c.item_unit?.item?.id}`" class="font-semibold text-zinc-900 transition hover:text-brand-600" x-text="c.item_unit?.item?.name ?? '—'"></a>
                                <p class="mt-0.5 font-mono text-xs text-zinc-400" x-text="c.item_unit?.serial_number ?? c.item_unit?.asset_tag ?? ''"></p>
                            </td>
                            <td class="table-td text-sm text-zinc-600" x-text="fmt.fmtDate(c.calibration_date)"></td>
                            <td class="table-td text-sm text-zinc-600" x-text="fmt.fmtDate(c.next_calibration_date)"></td>
                            <td class="table-td">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(c.result)">
                                    <span x-text="fmt.statusLabel(c.result)"></span>
                                </span>
                            </td>
                            <td class="table-td text-sm text-zinc-600" x-text="c.calibrated_by ?? '—'"></td>
                            <td class="table-td text-right">
                                <div class="inline-flex items-center gap-1">
                                    <template x-if="$store.auth.isAny(['laboran', 'admin_sistem'])">
                                        <button type="button" class="btn btn-ghost btn-sm" title="Ubah" @click="openEdit(c)">
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
                <x-empty-state title="Belum ada data kalibrasi" subtitle="Belum ada kalibrasi yang tercatat." icon="thermometer" />
            </div>
        </div>

        <x-pagination />
    </div>

    <x-modal open="formOpen" title="Catat Kalibrasi" title-expr="editId ? 'Ubah Kalibrasi' : 'Catat Kalibrasi'" subtitle="Catat hasil kalibrasi unit." subtitle-expr="editId ? 'Perbarui data kalibrasi.' : 'Catat hasil kalibrasi unit.'" maxWidth="max-w-2xl">
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
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Tanggal kalibrasi</label>
                <input type="date" x-model="form.calibration_date" :class="errors.calibration_date ? 'input input-error' : 'input'" />
                <p x-show="errors.calibration_date" class="mt-1 text-xs text-rose-600" x-text="errors.calibration_date?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kalibrasi berikutnya</label>
                <input type="date" x-model="form.next_calibration_date" :class="errors.next_calibration_date ? 'input input-error' : 'input'" />
                <p x-show="errors.next_calibration_date" class="mt-1 text-xs text-rose-600" x-text="errors.next_calibration_date?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Hasil</label>
                <select x-model="form.result" :class="errors.result ? 'input input-error' : 'input'">
                    <option value="lulus">Lulus</option>
                    <option value="tidak_lulus">Tidak Lulus</option>
                </select>
                <p x-show="errors.result" class="mt-1 text-xs text-rose-600" x-text="errors.result?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">No. sertifikat</label>
                <input type="text" x-model="form.certificate_number" :class="errors.certificate_number ? 'input input-error' : 'input'" placeholder="SERT-001" />
                <p x-show="errors.certificate_number" class="mt-1 text-xs text-rose-600" x-text="errors.certificate_number?.[0]"></p>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Petugas kalibrasi</label>
                <input type="text" x-model="form.calibrated_by" :class="errors.calibrated_by ? 'input input-error' : 'input'" placeholder="Nama petugas / vendor" />
                <p x-show="errors.calibrated_by" class="mt-1 text-xs text-rose-600" x-text="errors.calibrated_by?.[0]"></p>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Catatan</label>
                <textarea x-model="form.notes" rows="3" :class="errors.notes ? 'input input-error' : 'input'" placeholder="Catatan kalibrasi..."></textarea>
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
@endsection
