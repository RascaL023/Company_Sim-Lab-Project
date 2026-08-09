@extends('layouts.app')

@section('title', 'Laporan')

<div x-data="reportsPage">
    <x-page-header title="Laporan" subtitle="Unduh laporan laboratorium dalam format PDF atau Excel." />

    <div class="card mt-6">
        <div class="border-b border-zinc-100 px-5 py-4">
            <h2 class="font-display text-base font-semibold text-zinc-900">Pilih laporan</h2>
        </div>
        <div class="divide-y divide-zinc-50">
            <div class="flex flex-wrap items-center gap-4 px-5 py-5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                    <x-icon name="clipboard-list" class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-zinc-900">Laporan Inventori</p>
                    <p class="text-sm text-zinc-500">Daftar seluruh item, stok, dan kondisi aset.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-secondary" :disabled="busy" @click="download('inventory', 'pdf')">
                        <x-icon name="download" class="h-4 w-4" />
                        PDF
                    </button>
                    <button type="button" class="btn btn-secondary" :disabled="busy" @click="download('inventory', 'excel')">
                        <x-icon name="download" class="h-4 w-4" />
                        Excel
                    </button>
                </div>
            </div>

            <div class="px-5 py-5">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-sky-50 text-sky-600">
                        <x-icon name="rotate-ccw" class="h-5 w-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-zinc-900">Laporan Peminjaman</p>
                        <p class="text-sm text-zinc-500">Riwayat peminjaman, dapat difilter berdasarkan rentang tanggal.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <input type="date" x-model="from" class="input !w-auto" aria-label="Dari tanggal" />
                        <input type="date" x-model="to" class="input !w-auto" aria-label="Sampai tanggal" />
                        <button type="button" class="btn btn-secondary" :disabled="busy" @click="download('borrowings', 'pdf')">
                            <x-icon name="download" class="h-4 w-4" />
                            PDF
                        </button>
                        <button type="button" class="btn btn-secondary" :disabled="busy" @click="download('borrowings', 'excel')">
                            <x-icon name="download" class="h-4 w-4" />
                            Excel
                        </button>
                    </div>
                </div>
                <p x-show="to && from && to < from" class="mt-2 text-xs text-rose-600">Tanggal akhir harus setelah tanggal awal.</p>
            </div>

            <div class="flex flex-wrap items-center gap-4 px-5 py-5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                    <x-icon name="alert-circle" class="h-5 w-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-zinc-900">Laporan Aset Rusak</p>
                    <p class="text-sm text-zinc-500">Daftar aset yang rusak ringan, berat, atau hilang.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-secondary" :disabled="busy" @click="download('damaged-assets', 'pdf')">
                        <x-icon name="download" class="h-4 w-4" />
                        PDF
                    </button>
                    <button type="button" class="btn btn-secondary" :disabled="busy" @click="download('damaged-assets', 'excel')">
                        <x-icon name="download" class="h-4 w-4" />
                        Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
