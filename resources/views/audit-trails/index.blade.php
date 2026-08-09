@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<div x-data="auditTrailsPage">
    <x-page-header title="Audit Trail" subtitle="Jejak perubahan seluruh entitas sistem (read-only)." />

    <div class="card mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 p-4">
            <select x-model="filters.action" @change="applyFilters()" class="input !w-auto">
                <option value="">Semua aksi</option>
                <option value="created">created</option>
                <option value="updated">updated</option>
                <option value="deleted">deleted</option>
                <option value="restored">restored</option>
                <option value="status_changed">status_changed</option>
                <option value="login">login</option>
                <option value="logout">logout</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px]">
                <thead class="bg-zinc-50/70">
                    <tr>
                        <th class="table-th">Waktu</th>
                        <th class="table-th">Aksi</th>
                        <th class="table-th">Entitas</th>
                        <th class="table-th">User</th>
                        <th class="table-th">Perubahan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100">
                    <template x-for="a in items" :key="a.id">
                        <tr class="transition hover:bg-zinc-50/60">
                            <td class="table-td whitespace-nowrap text-sm text-zinc-500" x-text="fmt.fmtDateTime(a.created_at)"></td>
                            <td class="table-td">
                                <span class="inline-flex rounded-lg bg-zinc-100 px-2 py-0.5 text-xs font-medium" :class="a.action === 'deleted' ? 'text-rose-600' : (a.action === 'created' ? 'text-emerald-600' : 'text-zinc-600')" x-text="a.action"></span>
                            </td>
                            <td class="table-td">
                                <p class="text-sm font-medium text-zinc-800" x-text="fmt.modelName(a.auditable_type)"></p>
                                <p class="font-mono text-xs text-zinc-400" x-text="`#${a.auditable_id}`"></p>
                            </td>
                            <td class="table-td text-sm text-zinc-600" x-text="a.user?.name ?? '—'"></td>
                            <td class="table-td">
                                <p x-show="valuesPreview(a.new_values) || valuesPreview(a.old_values)" class="max-w-[260px] truncate text-xs text-zinc-500">
                                    <span x-text="valuesPreview(a.new_values) ?? valuesPreview(a.old_values)"></span>
                                </p>
                                <p x-show="!(valuesPreview(a.new_values) || valuesPreview(a.old_values))" class="text-xs text-zinc-300">—</p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <div x-show="loading" class="flex items-center justify-center py-16">
                <div class="h-8 w-8 animate-spin rounded-full border-2 border-zinc-200 border-t-brand-600"></div>
            </div>

            <div x-show="!loading && !items.length" x-cloak>
                <x-empty-state title="Belum ada audit trail" subtitle="Belum ada jejak perubahan yang cocok dengan filter." icon="file" />
            </div>
        </div>

        <x-pagination />
    </div>
</div>
@endsection
