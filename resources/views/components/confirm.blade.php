<div x-show="$store.confirm.open" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center bg-zinc-950/50 px-4 backdrop-blur-sm">
    <div class="absolute inset-0" @click="$store.confirm.cancel()"></div>
    <div x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        class="relative w-full max-w-sm card p-6 shadow-card-hover">
        <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl" :class="$store.confirm.danger ? 'bg-rose-50 text-rose-600' : 'bg-brand-50 text-brand-600'">
            <x-icon name="alert" x-show="$store.confirm.danger" class="h-5 w-5" />
            <x-icon name="info" x-show="!$store.confirm.danger" class="h-5 w-5" />
        </div>
        <h3 class="font-display text-base font-semibold text-zinc-900" x-text="$store.confirm.title"></h3>
        <p class="mt-1 text-sm text-zinc-500" x-text="$store.confirm.message"></p>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" class="btn btn-secondary" @click="$store.confirm.cancel()">Batal</button>
            <button type="button" :class="$store.confirm.danger ? 'btn btn-danger' : 'btn btn-primary'" x-text="$store.confirm.confirmLabel" @click="$store.confirm.accept()"></button>
        </div>
    </div>
</div>
