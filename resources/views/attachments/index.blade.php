@extends('layouts.app')

@section('title', 'Lampiran')

@section('content')
<div x-data="attachmentsPage">
    <x-page-header title="Lampiran" subtitle="Dokumen dan bukti yang menempel pada entitas laboratorium.">
        <x-slot:actions>
            <button type="button" x-show="$store.auth.isAny(['laboran', 'admin_sistem'])" @click="openUpload()" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Unggah Lampiran
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="card mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px]">
                <thead class="bg-zinc-50/70">
                    <tr>
                        <th class="table-th">File</th>
                        <th class="table-th">Terlampir pada</th>
                        <th class="table-th">Tipe</th>
                        <th class="table-th">Ukuran</th>
                        <th class="table-th">Diunggah oleh</th>
                        <th class="table-th text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="a in items" :key="a.id">
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="table-td">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                                        <x-icon name="file" class="h-4 w-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="max-w-[220px] truncate text-sm font-semibold text-zinc-900" x-text="a.original_filename"></p>
                                        <p x-show="a.description" class="max-w-[220px] truncate text-xs text-zinc-400" x-text="a.description"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="table-td">
                                <p class="text-sm font-medium text-zinc-800" x-text="fmt.modelName(a.attachable_type)"></p>
                                <p class="font-mono text-xs text-zinc-400" x-text="`#${a.attachable_id}`"></p>
                            </td>
                            <td class="table-td text-sm text-zinc-600" x-text="a.type ?? '—'"></td>
                            <td class="table-td text-sm text-zinc-500" x-text="a.size_human ?? '—'"></td>
                            <td class="table-td text-sm text-zinc-600" x-text="a.uploaded_by?.name ?? '—'"></td>
                            <td class="table-td text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button type="button" class="btn btn-ghost btn-sm" title="Unduh" @click="download(a)">
                                        <x-icon name="download" class="h-3.5 w-3.5" />
                                    </button>
                                    <template x-if="$store.auth.isAny(['laboran', 'admin_sistem'])">
                                        <button type="button" class="btn btn-ghost btn-sm !text-rose-500 hover:!bg-rose-50" title="Hapus" @click="remove(a)">
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
                <x-empty-state title="Belum ada lampiran" subtitle="Belum ada lampiran yang diunggah." icon="paperclip" />
            </div>
        </div>

        <x-pagination />
    </div>

    <x-modal open="uploadOpen" title="Unggah Lampiran" subtitle="Lampirkan dokumen pada entitas yang dipilih.">
        <form class="grid grid-cols-1 gap-4" @submit.prevent="upload()">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Jenis entitas</label>
                <select x-model="form.attachable_type" :class="errors.attachable_type ? 'input input-error' : 'input'">
                    <option value="">— Pilih entitas —</option>
                    <template x-for="opt in attachableOptions" :key="opt.value">
                        <option :value="opt.value" x-text="opt.label"></option>
                    </template>
                </select>
                <p x-show="errors.attachable_type" class="mt-1 text-xs text-rose-600" x-text="errors.attachable_type?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">ID entitas</label>
                <input type="number" min="1" x-model="form.attachable_id" :class="errors.attachable_id ? 'input input-error' : 'input'" placeholder="ID record" />
                <p x-show="errors.attachable_id" class="mt-1 text-xs text-rose-600" x-text="errors.attachable_id?.[0]"></p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Tipe (opsional)</label>
                <input type="text" x-model="form.type" class="input" placeholder="sertifikat, invoice, dll." />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">Deskripsi (opsional)</label>
                <input type="text" x-model="form.description" class="input" placeholder="Deskripsi singkat" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-zinc-700">File</label>
                <input type="file" @change="onFile($event)" class="block w-full text-sm text-zinc-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100" />
                <p x-show="errors.file" class="mt-1 text-xs text-rose-600" x-text="errors.file?.[0]"></p>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" @click="uploadOpen = false">Batal</button>
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    <span>Unggah</span>
                </button>
            </div>
        </form>
    </x-modal>
</div>
@endsection
