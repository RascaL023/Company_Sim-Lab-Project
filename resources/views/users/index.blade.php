@extends('layouts.app')

@section('title', 'Pengguna')

<div x-data="usersPage">
    <x-page-header title="Pengguna" subtitle="Manajemen akun pengguna laboratorium.">
        <x-slot:actions>
            <button type="button" x-show="auth.isAny(['admin_sistem'])" @click="openCreate()" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah Pengguna
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="card mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 p-4">
            <div class="relative min-w-0 flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                <input type="text" x-model="filters.search" @input.debounce.300ms="applyFilters()" class="input !pl-10" placeholder="Cari nama atau email..." />
            </div>
            <select x-model="filters.role" @change="applyFilters()" class="input !w-auto">
                <option value="">Semua role</option>
                <option value="admin_sistem">Admin Sistem</option>
                <option value="laboran">Laboran</option>
                <option value="kepala_lab">Kepala Lab</option>
                <option value="peminjam">Peminjam</option>
            </select>
            <select x-model="filters.is_active" @change="applyFilters()" class="input !w-auto">
                <option value="">Semua status</option>
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="bg-zinc-50/70">
                    <tr>
                        <th class="table-th">Nama</th>
                        <th class="table-th">Role</th>
                        <th class="table-th">Kontak</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="u in items" :key="u.id">
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="table-td">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-50 text-[11px] font-bold text-brand-600" x-text="fmt.initials(u.name)"></div>
                                    <div>
                                        <p class="text-sm font-medium text-zinc-800" x-text="u.name"></p>
                                        <p class="text-xs text-zinc-400" x-text="u.email"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-td">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset ring-zinc-500/20 bg-zinc-100 text-zinc-700">
                                    <span x-text="fmt.roleLabel(u.role)"></span>
                                </span>
                            </td>
                            <td class="table-td text-sm text-zinc-600" x-text="u.phone ?? '—'"></td>
                            <td class="table-td">
                                <template x-if="auth.isAny(['admin_sistem'])">
                                    <button type="button" class="inline-flex items-center gap-2 text-sm font-medium" :class="u.is_active ? 'text-emerald-600' : 'text-zinc-400'" @click="toggleActive(u)">
                                        <span class="relative inline-flex h-5 w-9 items-center rounded-full transition" :class="u.is_active ? 'bg-emerald-500' : 'bg-zinc-300'">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition" :class="u.is_active ? 'translate-x-4' : 'translate-x-0.5'"></span>
                                        </span>
                                        <span x-text="u.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                    </button>
                                </template>
                                <template x-if="!auth.isAny(['admin_sistem'])">
                                    <span class="text-sm" :class="u.is_active ? 'text-emerald-600' : 'text-zinc-400'" x-text="u.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                </template>
                            </td>
                            <td class="table-td text-right">
                                <div class="inline-flex items-center gap-1">
                                    <template x-if="auth.isAny(['admin_sistem'])">
                                        <button type="button" class="btn btn-ghost btn-sm" title="Ubah" @click="openEdit(u)">
                                            <x-icon name="pencil" class="h-3.5 w-3.5" />
                                        </button>
                                    </template>
                                    <template x-if="auth.isAny(['admin_sistem'])">
                                        <button type="button" class="btn btn-ghost btn-sm !text-rose-500 hover:!bg-rose-50" title="Hapus" @click="remove(u)">
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
                <x-empty-state title="Belum ada pengguna" subtitle="Belum ada pengguna yang cocok dengan filter." icon="users" />
            </div>
        </div>

        <x-pagination />
    </div>

    <x-modal open="formOpen" title="Tambah Pengguna" title-expr="editId ? 'Ubah Pengguna' : 'Tambah Pengguna'" subtitle="Buat akun pengguna baru." subtitle-expr="editId ? 'Perbarui data akun pengguna.' : 'Buat akun pengguna baru.'">
        <form class="grid grid-cols-1 gap-4" @submit.prevent="save()">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Nama</label>
                <input type="text" x-model="form.name" :class="errors.name ? 'input input-error' : 'input'" placeholder="Nama lengkap" />
                <p x-show="errors.name" class="mt-1 text-xs text-rose-600" x-text="errors.name?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Email</label>
                <input type="email" x-model="form.email" :class="errors.email ? 'input input-error' : 'input'" placeholder="nama@example.com" />
                <p x-show="errors.email" class="mt-1 text-xs text-rose-600" x-text="errors.email?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Role</label>
                <select x-model="form.role" :class="errors.role ? 'input input-error' : 'input'">
                    <option value="admin_sistem">Admin Sistem</option>
                    <option value="laboran">Laboran</option>
                    <option value="kepala_lab">Kepala Lab</option>
                    <option value="peminjam">Peminjam</option>
                </select>
                <p x-show="errors.role" class="mt-1 text-xs text-rose-600" x-text="errors.role?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Telepon</label>
                <input type="text" x-model="form.phone" :class="errors.phone ? 'input input-error' : 'input'" placeholder="08xxxxxxxxxx" />
                <p x-show="errors.phone" class="mt-1 text-xs text-rose-600" x-text="errors.phone?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Password</label>
                <input type="password" x-model="form.password" :class="errors.password ? 'input input-error' : 'input'" :placeholder="editId ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter'" />
                <p x-show="errors.password" class="mt-1 text-xs text-rose-600" x-text="errors.password?.[0]"></p>
            </div>
            <label class="flex items-center gap-2 text-sm text-zinc-700">
                <input type="checkbox" x-model="form.is_active" class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
                Akun aktif
            </label>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="formOpen = false">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <span x-text="editId ? 'Simpan Perubahan' : 'Tambah Pengguna'"></span>
                </button>
            </div>
        </form>
    </x-modal>
</div>
