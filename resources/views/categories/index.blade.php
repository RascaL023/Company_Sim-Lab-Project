@extends('layouts.app')

@section('title', 'Kategori')

<div x-data="categoriesPage">
    <x-page-header title="Kategori" subtitle="Pengelompokan item berdasarkan alat dan bahan.">
        <x-slot:actions>
            <button type="button" x-show="auth.isAny(['laboran', 'admin_sistem'])" @click="openCreate()" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah Kategori
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="card mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 p-4">
            <div class="relative min-w-0 flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                <input type="text" x-model="filters.search" @input.debounce.300ms="applyFilters()" class="input !pl-10" placeholder="Cari kategori..." />
            </div>
            <select x-model="filters.type" @change="applyFilters()" class="input !w-auto">
                <option value="">Semua tipe</option>
                <option value="alat">Alat</option>
                <option value="bahan">Bahan</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[560px]">
                <thead class="bg-zinc-50/70">
                    <tr>
                        <th class="table-th">Nama</th>
                        <th class="table-th">Tipe</th>
                        <th class="table-th">Deskripsi</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="cat in items" :key="cat.id">
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="table-td font-semibold text-zinc-900" x-text="cat.name"></td>
                            <td class="table-td">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(cat.type)">
                                    <span x-text="fmt.typeLabel(cat.type)"></span>
                                </span>
                            </td>
                            <td class="table-td text-sm text-zinc-500" x-text="cat.description ?? '—'"></td>
                            <td class="table-td text-right">
                                <div class="inline-flex items-center gap-1">
                                    <template x-if="auth.isAny(['laboran', 'admin_sistem'])">
                                        <button type="button" class="btn btn-ghost btn-sm" title="Ubah" @click="openEdit(cat)">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        </button>
                                    </template>
                                    <template x-if="auth.isAny(['laboran', 'admin_sistem'])">
                                        <button type="button" class="btn btn-ghost btn-sm !text-rose-500 hover:!bg-rose-50" title="Hapus" @click="remove(cat)">
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
                <x-empty-state title="Belum ada kategori" subtitle="Belum ada kategori yang cocok dengan filter saat ini." icon="folder" />
            </div>
        </div>

        <x-pagination />
    </div>

    <x-modal open="formOpen" title="Tambah Kategori" title-expr="editId ? 'Ubah Kategori' : 'Tambah Kategori'" subtitle="Masukkan kategori baru." subtitle-expr="editId ? 'Perbarui data kategori.' : 'Masukkan kategori baru.'">
        <form class="grid grid-cols-1 gap-4" @submit.prevent="save()">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Nama</label>
                <input type="text" x-model="form.name" :class="errors.name ? 'input input-error' : 'input'" placeholder="Nama kategori" />
                <p x-show="errors.name" class="mt-1 text-xs text-rose-600" x-text="errors.name?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Tipe</label>
                <select x-model="form.type" :class="errors.type ? 'input input-error' : 'input'">
                    <option value="alat">Alat</option>
                    <option value="bahan">Bahan</option>
                </select>
                <p x-show="errors.type" class="mt-1 text-xs text-rose-600" x-text="errors.type?.[0]"></p>
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
                    <span x-text="editId ? 'Simpan Perubahan' : 'Tambah Kategori'"></span>
                </button>
            </div>
        </form>
    </x-modal>
</div>
