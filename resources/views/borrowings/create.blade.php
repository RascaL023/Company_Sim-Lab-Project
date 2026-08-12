@extends('layouts.app')

@section('title', 'Ajukan Peminjaman')

@section('content')
<div x-data="borrowingCreatePage">
    <x-page-header title="Ajukan Peminjaman" subtitle="Pilih item katalog dan jumlah yang diminta. Unit fisik alat ditentukan laboran saat checkout." />

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h2 class="font-display text-base font-semibold text-zinc-900">Pilih Item</h2>
            </div>
            <div class="p-5">
                <div x-show="itemsLoading" class="flex items-center justify-center py-12">
                    <div class="h-7 w-7 animate-spin rounded-full border-2 border-zinc-200 border-t-brand-600"></div>
                </div>

                <div x-show="!itemsLoading" class="grid grid-cols-1 gap-2">
                    <select id="item-picker" x-ref="picker" class="input" @change="addItem($event.target.value); $event.target.value = ''">
                        <option value="">— Pilih item —</option>
                        <template x-for="item in items" :key="item.id">
                            <option :value="item.id" x-text="itemOptionLabel(item)"></option>
                        </template>
                    </select>
                    <p class="text-xs text-zinc-400">Alat menampilkan unit tersedia; bahan menampilkan stok. Tidak perlu memilih nomor seri.</p>
                </div>

                <div class="mt-6">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">Item dipilih</p>
                    <div x-show="!selected.length" class="rounded-2xl border border-dashed border-zinc-200 py-10 text-center">
                        <x-icon name="package" class="mx-auto h-6 w-6 text-zinc-300" />
                        <p class="mt-2 text-sm text-zinc-400">Belum ada item dipilih.</p>
                    </div>
                    <div class="space-y-3">
                        <template x-for="(line, index) in selected" :key="line.item_id">
                            <div class="rounded-2xl border border-zinc-100 p-4">
                                <div class="flex items-start gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="truncate text-sm font-semibold text-zinc-900" x-text="line.item.name"></p>
                                            <span
                                                class="inline-flex rounded-md px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide ring-1 ring-inset"
                                                :class="line.item.is_alat ? 'bg-sky-50 text-sky-700 ring-sky-600/20' : 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'"
                                                x-text="line.item.is_alat ? 'Alat' : 'Bahan'"
                                            ></span>
                                        </div>
                                        <p class="mt-0.5 text-xs text-zinc-400" x-text="line.item.code"></p>
                                        <p class="mt-1 text-xs text-zinc-500" x-text="availabilityHint(line)"></p>
                                        <p x-show="line.item.is_alat" class="mt-1 text-xs text-zinc-400">Unit fisik (serial) dipilih laboran saat checkout.</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <label class="text-xs text-zinc-500">Jumlah</label>
                                        <input
                                            type="number"
                                            :min="line.item.is_alat ? 1 : 0.5"
                                            :step="line.item.is_alat ? 1 : 0.5"
                                            x-model.number="line.quantity"
                                            :class="lineError(index, 'quantity') ? 'input input-error !w-24' : 'input !w-24'"
                                        />
                                        <button type="button" class="btn btn-ghost btn-sm !text-rose-500 hover:!bg-rose-50" @click="removeLine(index)">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                                <p x-show="lineError(index, 'quantity')" class="mt-2 text-xs text-rose-600" x-text="lineError(index, 'quantity')"></p>
                                <p x-show="lineError(index, 'item_id')" class="mt-2 text-xs text-rose-600" x-text="lineError(index, 'item_id')"></p>
                                <p x-show="lineError(index, 'item_unit_id')" class="mt-2 text-xs text-rose-600" x-text="lineError(index, 'item_unit_id')"></p>
                                <p x-show="softWarn(line)" class="mt-2 text-xs font-medium text-amber-600" x-text="softWarn(line)"></p>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div class="card h-fit">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h2 class="font-display text-base font-semibold text-zinc-900">Pengajuan</h2>
            </div>
            <div class="space-y-4 p-5">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Tujuan peminjaman</label>
                    <input type="text" x-model="purpose" :class="errors.purpose ? 'input input-error' : 'input'" placeholder="Praktikum, penelitian..." />
                    <p x-show="errors.purpose" class="mt-1 text-xs text-rose-600" x-text="errors.purpose?.[0]"></p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Lampiran surat izin <span class="font-normal text-zinc-400">(opsional)</span></label>
                    <input type="file" class="input" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" @change="onAttachment($event)" />
                    <p class="mt-1 text-xs text-zinc-400">PDF/gambar/dokumen pendukung. Akan dilampirkan ke pengajuan setelah berhasil dibuat.</p>
                    <p x-show="attachmentFile" class="mt-1 truncate text-xs font-medium text-brand-700" x-text="attachmentFile?.name"></p>
                </div>
                <div class="flex items-center justify-between rounded-2xl bg-zinc-50 px-4 py-3">
                    <p class="text-sm text-zinc-500">Total baris</p>
                    <p class="font-display text-lg font-bold text-zinc-900" x-text="`${selected.length} jenis`"></p>
                </div>
                <div x-show="formErrorList().length" class="space-y-1.5 rounded-xl border border-rose-200 bg-rose-50 p-3">
                    <p class="text-xs font-semibold text-rose-700">Perbaiki data berikut:</p>
                    <template x-for="(msg, i) in formErrorList()" :key="i">
                        <p class="text-xs text-rose-600" x-text="msg"></p>
                    </template>
                </div>
                <button type="button" class="btn btn-primary w-full" :disabled="submitting || !selected.length" @click="submit()">
                    <span x-show="submitting" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <span>Ajukan Peminjaman</span>
                </button>
                <p class="text-xs text-zinc-400">Permintaan akan diteruskan ke laboran untuk persetujuan. Validasi stok/unit final tetap di server.</p>
            </div>
        </div>
    </div>
</div>
@endsection
