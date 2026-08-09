@extends('layouts.app')

@section('title', 'Ajukan Peminjaman')

@section('content')
<div x-data="borrowingCreatePage">
    <x-page-header title="Ajukan Peminjaman" subtitle="Pilih item yang ingin dipinjam dan tuliskan tujuan peminjaman." />

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
                            <option :value="item.id" x-text="`${item.code} — ${item.name} (stok ${fmt.fmtNum(item.stock_quantity)})`"></option>
                        </template>
                    </select>
                </div>

                <div class="mt-6">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-400">Item dipilih</p>
                    <div x-show="!selected.length" class="rounded-2xl border border-dashed border-zinc-200 py-10 text-center">
                        <x-icon name="package" class="mx-auto h-6 w-6 text-zinc-300" />
                        <p class="mt-2 text-sm text-zinc-400">Belum ada item dipilih.</p>
                    </div>
                    <div class="space-y-3">
                        <template x-for="(line, index) in selected" :key="line.item_id">
                            <div class="flex items-center gap-3 rounded-2xl border border-zinc-100 p-4">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-zinc-900" x-text="line.item.name"></p>
                                    <p class="mt-0.5 text-xs text-zinc-400" x-text="line.item.code"></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-zinc-500">Qty</label>
                                    <input type="number" min="0.5" step="0.5" x-model="line.quantity" class="input !w-24" />
                                </div>
                                <button type="button" class="btn btn-ghost btn-sm !text-rose-500 hover:!bg-rose-50" @click="removeLine(index)">
                                    <x-icon name="trash" class="h-4 w-4" />
                                </button>
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
                <div class="flex items-center justify-between rounded-2xl bg-zinc-50 px-4 py-3">
                    <p class="text-sm text-zinc-500">Total item</p>
                    <p class="font-display text-lg font-bold text-zinc-900" x-text="`${selected.length} jenis`"></p>
                </div>
                <div x-show="errors.items" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-600" x-text="errors.items?.[0]"></div>
                <button type="button" class="btn btn-primary w-full" :disabled="submitting || !selected.length" @click="submit()">
                    <span x-show="submitting" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <span>Ajukan Peminjaman</span>
                </button>
                <p class="text-xs text-zinc-400">Permintaan akan diteruskan ke laboran untuk persetujuan.</p>
            </div>
        </div>
    </div>
</div>
@endsection
