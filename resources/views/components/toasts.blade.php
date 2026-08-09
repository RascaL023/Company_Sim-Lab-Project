<div x-data class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4">
    <template x-for="t in $store.toast.items" :key="t.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-2xl border bg-white p-4 shadow-card-hover"
            :class="t.type === 'error' ? 'border-rose-200' : t.type === 'info' ? 'border-sky-200' : 'border-emerald-200'"
        >
            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-white"
                :class="t.type === 'error' ? 'bg-rose-500' : t.type === 'info' ? 'bg-sky-500' : 'bg-emerald-500'">
                <x-icon name="check" x-show="t.type !== 'error' && t.type !== 'info'" class="h-3.5 w-3.5" />
                <x-icon name="alert" x-show="t.type === 'error'" class="h-3.5 w-3.5" />
                <x-icon name="info" x-show="t.type === 'info'" class="h-3.5 w-3.5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-zinc-900" x-text="t.type === 'error' ? 'Terjadi kesalahan' : t.type === 'info' ? 'Perhatian' : 'Berhasil'"></p>
                <p class="mt-0.5 text-sm leading-snug text-zinc-500" x-text="t.message"></p>
            </div>
            <button type="button" @click="$store.toast.remove(t.id)" class="rounded-lg p-1 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-600">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    </template>
</div>
