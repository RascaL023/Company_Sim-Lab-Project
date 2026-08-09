@extends('layouts.app')

@section('title', 'Disposal')

@section('content')
<div x-data="disposalsPage" x-cloak>
    <x-page-header title="Disposal" subtitle="Usulkan dan tinjau penghapusan aset yang rusak total, kedaluwarsa, atau hilang." />

    <div class="mt-6 card overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 px-5 py-4">
            <h2 class="font-display text-base font-semibold text-zinc-900">Daftar Usulan</h2>
            <select x-model="filters.status" @change="applyFilters()" class="input ml-auto !w-auto">
                <option value="">Semua status</option>
                <option value="diusulkan">Diusulkan</option>
                <option value="disetujui">Disetujui</option>
                <option value="ditolak">Ditolak</option>
            </select>
        </div>

        <div x-show="listError" class="border-b border-rose-100 bg-rose-50 px-5 py-3 text-sm text-rose-700" x-text="listError"></div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="bg-zinc-50/70">
                    <tr>
                        <th class="table-th">ID</th>
                        <th class="table-th">Target</th>
                        <th class="table-th">Alasan</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Pengusul</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="d in list" :key="d.id">
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="table-td font-mono text-xs text-zinc-500" x-text="`#${d.id}`"></td>
                            <td class="table-td">
                                <p class="text-sm font-semibold text-zinc-900" x-text="targetLabel(d)"></p>
                                <p class="text-xs text-zinc-400" x-text="d.item_unit_id ? 'Unit alat' : 'Bahan'"></p>
                            </td>
                            <td class="table-td text-sm text-zinc-600" x-text="fmt.statusLabel(d.reason) === d.reason ? d.reason.replaceAll('_', ' ') : fmt.statusLabel(d.reason)"></td>
                            <td class="table-td">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(d.status)">
                                    <span x-text="fmt.statusLabel(d.status)"></span>
                                </span>
                            </td>
                            <td class="table-td text-sm text-zinc-600" x-text="d.proposed_by?.name ?? '—'"></td>
                            <td class="table-td text-right">
                                <div class="inline-flex gap-1">
                                    <template x-if="d.status === 'diusulkan' && $store.auth.isAny(['kepala_lab'])">
                                        <button type="button" class="btn btn-primary btn-sm" :disabled="busy" @click="approve(d)">Setujui</button>
                                    </template>
                                    <template x-if="d.status === 'diusulkan' && $store.auth.isAny(['kepala_lab'])">
                                        <button type="button" class="btn btn-secondary btn-sm" @click="openReject(d)">Tolak</button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div x-show="listLoading" class="flex items-center justify-center py-16">
                <div class="h-8 w-8 animate-spin rounded-full border-2 border-zinc-200 border-t-brand-600"></div>
            </div>
            <div x-show="!listLoading && !list.length" x-cloak>
                <x-empty-state title="Belum ada usulan" subtitle="Belum ada usulan disposal yang cocok dengan filter." icon="trash" />
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3" x-show="$store.auth.isAny(['laboran', 'admin_sistem'])">
        <div class="card lg:col-span-2">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h2 class="font-display text-base font-semibold text-zinc-900">Ajukan Usulan</h2>
            </div>
            <div class="p-5">
                <div x-show="optionsLoading" class="flex items-center justify-center py-12">
                    <div class="h-7 w-7 animate-spin rounded-full border-2 border-zinc-200 border-t-brand-600"></div>
                </div>

                <div x-show="!optionsLoading" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
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
                        <p class="mt-0.5 text-xs text-zinc-500">Laboran/Admin mengajukan usulan berstatus <span class="font-medium">diusulkan</span>.</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-sm font-bold text-zinc-500">2</div>
                    <div>
                        <p class="text-sm font-semibold text-zinc-800">Persetujuan</p>
                        <p class="mt-0.5 text-xs text-zinc-500">Hanya <span class="font-medium">Kepala Lab</span> yang dapat menyetujui atau menolak.</p>
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

    <x-modal open="rejectOpen" title="Tolak Disposal" subtitle="Sampaikan alasan penolakan usulan ini.">
        <div class="grid gap-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Alasan penolakan</label>
                <textarea x-model="rejectionReason" rows="3" class="input" placeholder="Alasan penolakan..."></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="rejectOpen = false">Batal</button>
                <button type="button" class="btn btn-danger" :disabled="busy || !rejectionReason.trim()" @click="reject()">Tolak Usulan</button>
            </div>
        </div>
    </x-modal>
</div>
@endsection
