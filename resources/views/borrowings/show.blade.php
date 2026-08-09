@extends('layouts.app')

@section('title', 'Detail Peminjaman')

<div x-data="borrowingDetailPage({ id: '{{ $borrowingRequest }}' })">
    <a href="/borrowings" class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 transition hover:text-brand-600">
        <x-icon name="chevron-left" class="h-4 w-4" />
        Kembali ke Peminjaman
    </a>

    <div x-show="error" x-cloak class="mt-6 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4">
        <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" />
        <p class="text-sm font-medium text-rose-700" x-text="error"></p>
    </div>

    <div x-show="loading" x-cloak class="mt-6 animate-pulse">
        <div class="card p-6">
            <div class="h-6 w-64 rounded bg-zinc-100"></div>
            <div class="mt-3 h-4 w-40 rounded bg-zinc-100"></div>
        </div>
    </div>

    <template x-if="request && !loading">
        <div>
            <div class="mt-6 rounded-2xl border border-zinc-100 bg-white p-6 shadow-card">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex rounded-lg bg-zinc-100 px-2 py-0.5 font-mono text-xs font-medium text-zinc-600" x-text="request.request_number"></span>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="fmt.badgeClass(request.status)">
                                <span class="h-1.5 w-1.5 rounded-full bg-current opacity-60"></span>
                                <span x-text="fmt.statusLabel(request.status)"></span>
                            </span>
                        </div>
                        <h1 class="mt-3 font-display text-2xl font-bold tracking-tight text-zinc-900" x-text="request.purpose ?? 'Peminjaman'"></h1>
                        <p class="mt-1 text-sm text-zinc-500" x-text="`oleh ${request.requested_by?.name ?? '—'} · ${fmt.fmtDateTime(request.requested_at)}`"></p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <template x-if="request.status === 'diajukan' && auth.isAny(['laboran', 'admin_sistem'])">
                            <button type="button" class="btn btn-primary" :disabled="busy" @click="approve(request)">
                                <x-icon name="check" class="h-4 w-4" />
                                Setujui
                            </button>
                        </template>
                        <template x-if="request.status === 'diajukan' && auth.isAny(['laboran', 'admin_sistem'])">
                            <button type="button" class="btn btn-secondary" @click="openReject(request)">
                                <x-icon name="x" class="h-4 w-4" />
                                Tolak
                            </button>
                        </template>
                        <template x-if="isCancellable()">
                            <button type="button" class="btn btn-ghost !text-amber-600" @click="cancel(request)">
                                <x-icon name="ban" class="h-4 w-4" />
                                Batalkan
                            </button>
                        </template>
                    </div>
                </div>

                <dl class="mt-6 grid grid-cols-2 gap-4 border-t border-zinc-100 pt-6 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-zinc-400">Total item</dt>
                        <dd class="mt-1 text-sm font-medium text-zinc-800" x-text="`${itemQuantity()} item`"></dd>
                    </div>
                    <template x-if="request.approved_by">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-400">Disetujui oleh</dt>
                            <dd class="mt-1 text-sm font-medium text-zinc-800" x-text="`${request.approved_by?.name} · ${fmt.fmtDateTime(request.approved_at)}`"></dd>
                        </div>
                    </template>
                    <template x-if="request.notes">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-400">Catatan</dt>
                            <dd class="mt-1 text-sm text-zinc-600" x-text="request.notes"></dd>
                        </div>
                    </template>
                </dl>
            </div>

            <template x-if="request.rejection_reason">
                <div class="mt-4 flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4">
                    <x-icon name="alert" class="mt-0.5 h-4 w-4 shrink-0 text-rose-500" />
                    <div>
                        <p class="text-sm font-semibold text-rose-700">Alasan penolakan</p>
                        <p class="mt-0.5 text-sm text-rose-600" x-text="request.rejection_reason"></p>
                    </div>
                </div>
            </template>

            <div class="card mt-6 overflow-hidden">
                <div class="border-b border-zinc-100 px-5 py-4">
                    <h2 class="font-display text-base font-semibold text-zinc-900">Daftar Item</h2>
                </div>
                <div class="divide-y divide-zinc-50">
                    <template x-for="bi in request.items ?? []" :key="bi.id">
                        <div class="flex flex-wrap items-center gap-4 px-5 py-4">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-zinc-900" x-text="bi.item?.name ?? '—'"></p>
                                <p class="mt-0.5 text-xs text-zinc-400" x-text="`${bi.item?.code ?? ''} · Qty ${fmt.fmtNum(bi.quantity)}`"></p>
                            </div>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-zinc-500">
                                <span x-show="bi.borrow_date" x-text="`Dipinjam ${fmt.fmtDateTime(bi.borrow_date)}`"></span>
                                <span x-show="bi.expected_return_date" x-text="`Estimasi ${fmt.fmtDate(bi.expected_return_date)}`"></span>
                                <span x-show="bi.actual_return_date" x-text="`Kembali ${fmt.fmtDate(bi.actual_return_date)}`"></span>
                                <span x-show="bi.condition_after" x-text="fmt.conditionLabel(bi.condition_after)"></span>
                                <span x-show="bi.is_damaged" class="font-medium text-rose-600">Rusak</span>
                            </div>
                            <div class="flex gap-2">
                                <template x-if="request.status === 'disetujui' && !bi.borrow_date && auth.isAny(['laboran', 'admin_sistem'])">
                                    <button type="button" class="btn btn-primary btn-sm" @click="openCheckout(bi)">
                                        <x-icon name="package" class="h-3.5 w-3.5" />
                                        Checkout
                                    </button>
                                </template>
                                <template x-if="bi.borrow_date && !bi.actual_return_date && auth.isAny(['laboran', 'admin_sistem'])">
                                    <button type="button" class="btn btn-secondary btn-sm" @click="openReturn(bi)">
                                        <x-icon name="rotate-ccw" class="h-3.5 w-3.5" />
                                        Return
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Reject modal --}}
            <x-modal open="rejectOpen" title="Tolak Peminjaman" subtitle="Sampaikan alasan penolakan permintaan ini.">
                <div class="grid gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700">Alasan penolakan</label>
                        <textarea x-model="rejectionReason" rows="3" class="input" placeholder="Alasan penolakan..."></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn btn-secondary" @click="rejectOpen = false">Batal</button>
                        <button type="button" class="btn btn-danger" :disabled="busy || !rejectionReason.trim()" @click="reject()">Tolak Permintaan</button>
                    </div>
                </div>
            </x-modal>

            {{-- Checkout modal --}}
            <x-modal open="checkoutOpen" title="Checkout Item" subtitle="Serahkan item kepada peminjam.">
                <div class="grid gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700">Estimasi tanggal kembali</label>
                        <input type="date" x-model="expectedReturnDate" class="input" />
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn btn-secondary" @click="checkoutOpen = false">Batal</button>
                        <button type="button" class="btn btn-primary" :disabled="busy || !expectedReturnDate" @click="doCheckout()">Konfirmasi Checkout</button>
                    </div>
                </div>
            </x-modal>

            {{-- Return modal --}}
            <x-modal open="returnOpen" title="Return Item" subtitle="Periksa dan catat kondisi item yang dikembalikan.">
                <div class="grid gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700">Kondisi setelah peminjaman</label>
                        <select x-model="returnForm.condition_after" class="input">
                            <option value="baik">Baik</option>
                            <option value="rusak_ringan">Rusak ringan</option>
                            <option value="rusak_berat">Rusak berat</option>
                            <option value="hilang">Hilang</option>
                        </select>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-zinc-700">
                        <input type="checkbox" x-model="returnForm.is_damaged" class="h-4 w-4 rounded border-zinc-300 text-brand-600 focus:ring-brand-500" />
                        Item rusak
                    </label>
                    <div x-show="returnForm.is_damaged">
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700">Catatan kerusakan</label>
                        <textarea x-model="returnForm.damage_notes" rows="3" class="input" placeholder="Deskripsi kerusakan..."></textarea>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-zinc-700">Catatan pemeriksaan</label>
                        <textarea x-model="returnForm.check_notes" rows="3" class="input" placeholder="Hasil pemeriksaan..."></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn btn-secondary" @click="returnOpen = false">Batal</button>
                        <button type="button" class="btn btn-primary" :disabled="busy || (returnForm.is_damaged && !returnForm.damage_notes.trim())" @click="doReturn()">Konfirmasi Return</button>
                    </div>
                </div>
            </x-modal>
        </div>
    </template>
</div>
