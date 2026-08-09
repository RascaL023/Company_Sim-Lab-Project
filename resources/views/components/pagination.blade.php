<nav x-show="meta" class="flex flex-wrap items-center justify-between gap-4 border-t border-zinc-100 px-5 py-4">
    <p class="text-sm text-zinc-500">
        Menampilkan
        <span class="font-semibold text-zinc-700" x-text="meta ? (meta.from ?? 0) : 0"></span>–
        <span class="font-semibold text-zinc-700" x-text="meta ? (meta.to ?? 0) : 0"></span>
        dari
        <span class="font-semibold text-zinc-700" x-text="meta ? meta.total : 0"></span>
    </p>
    <div class="flex items-center gap-1">
        <button
            type="button"
            class="btn btn-secondary btn-sm"
            :disabled="!meta || meta.current_page <= 1"
            @click="setPage(meta.current_page - 1)"
        >
            <x-icon name="chevron-left" class="h-3.5 w-3.5" />
            <span class="hidden sm:inline">Sebelumnya</span>
        </button>

        <template x-for="p in pageNumbers()" :key="p">
            <template x-if="p === '…'">
                <span class="px-1.5 text-sm text-zinc-400">…</span>
            </template>
            <template x-if="p !== '…'">
                <button
                    type="button"
                    class="h-8 w-8 rounded-lg text-sm font-medium transition"
                    :class="p === meta.current_page ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/20' : 'text-zinc-600 hover:bg-zinc-100'"
                    @click="setPage(p)"
                    x-text="p"
                ></button>
            </template>
        </template>

        <button
            type="button"
            class="btn btn-secondary btn-sm"
            :disabled="!meta || meta.current_page >= meta.last_page"
            @click="setPage(meta.current_page + 1)"
        >
            <span class="hidden sm:inline">Berikutnya</span>
            <x-icon name="chevron-right" class="h-3.5 w-3.5" />
        </button>
    </div>
</nav>
