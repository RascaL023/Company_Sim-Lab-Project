@extends('layouts.app')

@section('title', 'Lokasi')

@section('content')
<div x-data="locationsPage">
    <x-page-header title="Lokasi" subtitle="Lokasi fisik untuk unit alat dan bahan.">
        <x-slot:actions>
            <button type="button" x-show="$store.auth.isAny(['admin_sistem'])" @click="openCreate()" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah Lokasi
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="card mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 p-4">
            <div class="relative min-w-0 flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                <input type="text" x-model="search" @keyup.enter="doSearch()" class="input !pl-10" placeholder="Cari lokasi..." />
            </div>
            <button type="button" class="btn btn-secondary btn-sm" @click="clearSearch()">Reset</button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px]">
                <thead class="bg-zinc-50/70">
                    <tr>
                        <th class="table-th">Kode</th>
                        <th class="table-th">Nama</th>
                        <th class="table-th">Deskripsi</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="loc in items" :key="loc.id">
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="table-td">
                                <span class="inline-flex rounded-lg bg-zinc-100 px-2 py-0.5 font-mono text-xs font-medium text-zinc-600" x-text="loc.code"></span>
                            </td>
                            <td class="table-td font-semibold text-zinc-900" x-text="loc.name"></td>
                            <td class="table-td text-sm text-zinc-500" x-text="loc.description ?? '—'"></td>
                            <td class="table-td text-right">
                                <div class="inline-flex items-center gap-1">
                                    <a :href="`/item-units?location_id=${loc.id}`" class="btn btn-ghost btn-sm" title="Lihat unit di lokasi ini">
                                        <x-icon name="eye" class="h-3.5 w-3.5" />
                                    </a>
                                    <template x-if="$store.auth.isAny(['admin_sistem'])">
                                        <button type="button" class="btn btn-ghost btn-sm" title="Ubah" @click="openEdit(loc)">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        </button>
                                    </template>
                                    <template x-if="$store.auth.isAny(['admin_sistem'])">
                                        <button type="button" class="btn btn-ghost btn-sm !text-rose-500 hover:!bg-rose-50" title="Hapus" @click="remove(loc)">
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
                <x-empty-state title="Belum ada lokasi" subtitle="Belum ada lokasi yang cocok dengan pencarian." icon="map-pin" />
            </div>
        </div>

        <x-pagination />
    </div>

    <x-modal open="formOpen" title="Tambah Lokasi" title-expr="editId ? 'Ubah Lokasi' : 'Tambah Lokasi'" subtitle="Masukkan lokasi baru." subtitle-expr="editId ? 'Perbarui data lokasi.' : 'Masukkan lokasi baru.'">
        <form class="grid grid-cols-1 gap-4" @submit.prevent="save()">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kode</label>
                <input type="text" x-model="form.code" :class="errors.code ? 'input input-error' : 'input'" placeholder="LAB-1" />
                <p x-show="errors.code" class="mt-1 text-xs text-rose-600" x-text="errors.code?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Nama</label>
                <input type="text" x-model="form.name" :class="errors.name ? 'input input-error' : 'input'" placeholder="Nama lokasi" />
                <p x-show="errors.name" class="mt-1 text-xs text-rose-600" x-text="errors.name?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Deskripsi</label>
                <textarea x-model="form.description" rows="3" :class="errors.description ? 'input input-error' : 'input'" placeholder="Deskripsi singkat..."></textarea>
                <p x-show="errors.description" class="mt-1 text-xs text-rose-600" x-text="errors.description?.[0]"></p>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="formOpen = false">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <span x-text="editId ? 'Simpan Perubahan' : 'Tambah Lokasi'"></span>
                </button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
