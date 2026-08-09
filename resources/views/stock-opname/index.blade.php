@extends('layouts.app')

@section('title', 'Stock Opname')

<div x-data="stockOpnamePage" x-cloak>
    <x-page-header title="Stock Opname" subtitle="Sesuaikan stok fisik dengan pencatatan melalui sesi opname." />

    <div x-show="result" class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                <x-icon name="check" class="h-5 w-5" />
            </div>
            <div class="flex-1">
                <p class="font-display text-base font-bold text-emerald-900">Stock opname selesai</p>
                <p class="mt-1 text-sm text-emerald-700" x-text="`${result.summary?.items_submitted ?? 0} item diproses, ${result.summary?.items_adjusted ?? 0} disesuaikan, ${result.summary?.items_unchanged ?? 0} tidak berubah.`"></p>
                <div class="mt-4 grid gap-2 sm:grid-cols-3">
                    <template x-for="m in result.movements ?? []" :key="m.id">
                        <div class="rounded-xl bg-white/70 p-3">
                            <p class="text-sm font-semibold text-zinc-800" x-text="m.item?.name ?? '—'"></p>
                            <p class="mt-0.5 text-xs text-zinc-500" x-text="`${fmt.fmtNum(m.quantity_before)} → ${fmt.fmtNum(m.quantity_after)} ${m.item?.unit ?? ''}`"></p>
                        </div>
                    </template>
                </div>
                <div x-show="!(result.movements ?? []).length" class="mt-3 rounded-xl bg-white/70 p-3 text-sm text-zinc-600">Semua stok sudah sesuai, tidak ada penyesuaian.</div>
                <button type="button" class="btn btn-secondary btn-sm mt-4" @click="result = null">Buat Sesi Baru</button>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="border-b border-zinc-100 px-5 py-4">
                <h2 class="font-display text-base font-semibold text-zinc-900">Item yang Dicek</h2>
            </div>
            <div class="p-5">
                <div x-show="loading" class="flex items-center justify-center py-12">
                    <div class="h-7 w-7 animate-spin rounded-full border-2 border-zinc-200 border-t-brand-600"></div>
                </div>

                <div x-show="!loading" class="grid grid-cols-1 gap-2">
                    <select x-ref="picker" class="input" @change="addItem($event.target.value); $event.target.value = ''">
                        <option value="">— Pilih item —</option>
                        <template x-for="item in items" :key="item.id">
                            <option :value="item.id" x-text="`${item.code} — ${item.name} (stok ${fmt.fmtNum(item.stock_quantity)})`"></option>
                        </template>
                    </select>
                </div>

                <div class="mt-6">
                    <div x-show="!lines.length" class="rounded-2xl border border-dashed border-zinc-200 py-10 text-center">
                        <x-icon name="clipboard-check" class="mx-auto h-6 w-6 text-zinc-300" />
                        <p class="mt-2 text-sm text-zinc-400">Belum ada item yang dicek.</p>
                    </div>
                    <div class="space-y-3">
                        <template x-for="(line, index) in lines" :key="line.item_id">
                            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-zinc-100 p-4">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-zinc-900" x-text="line.item.name"></p>
                                    <p class="mt-0.5 text-xs text-zinc-400" x-text="`${line.item.code} · stok sistem ${fmt.fmtNum(line.item.stock_quantity)}`"></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-zinc-500">Stok fisik</label>
                                    <input type="number" min="0" step="0.5" x-model="line.counted_quantity" class="input !w-24" />
                                </div>
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="diff(line) === 0 ? 'bg-zinc-50 text-zinc-500 ring-zinc-600/10' : (diff(line) > 0 ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20')">
                                    <span x-text="diff(line) === 0 ? 'Sesuai' : (diff(line) > 0 ? `+${fmt.fmtNum(diff(line))}` : `${fmt.fmtNum(diff(line))}`)"></span>
                                </span>
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
                <h2 class="font-display text-base font-semibold text-zinc-900">Sesi Opname</h2>
            </div>
            <div class="space-y-4 p-5">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-700">Catatan</label>
                    <textarea x-model="notes" rows="3" class="input" placeholder="Catatan sesi opname (opsional)..."></textarea>
                </div>
                <div x-show="errors.items" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-xs text-rose-600" x-text="errors.items?.[0]"></div>
                <button type="button" class="btn btn-primary w-full" :disabled="submitting || !lines.length" @click="submit()">
                    <span x-show="submitting" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <span>Jalankan Opname</span>
                </button>
                <p class="text-xs text-zinc-400">Perbedaan stok akan dicatat sebagai mutasi penyesuaian.</p>
            </div>
        </div>
    </div>
</div>
