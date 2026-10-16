@props(['action', 'message' => 'Data yang dihapus tidak dapat dipulihkan.'])
<div x-data="{ open: false }" class="inline-block">
    <button type="button" class="brutal-btn min-h-10 bg-danger px-3 text-sm" @click="open = true" aria-label="Hapus data">
        <x-icon name="trash" class="h-4 w-4" /> Hapus
    </button>
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[80] grid place-items-center bg-ink/60 p-4" @keydown.escape.window="open = false" @click.self="open = false">
            <div x-show="open" x-transition class="brutal-card w-full max-w-md p-5" role="alertdialog" aria-modal="true">
                <div class="mb-4 flex items-start gap-3">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-button border-2 border-ink bg-danger"><x-icon name="alert" class="h-6 w-6" /></span>
                    <div><h2 class="text-xl font-extrabold">Hapus data ini?</h2><p class="mt-1 text-sm">{{ $message }}</p></div>
                </div>
                <div class="flex justify-end gap-3">
                    <x-button variant="neutral" @click="open = false">Batal</x-button>
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        @method('DELETE')
                        <x-button variant="danger" type="submit">Ya, hapus</x-button>
                    </form>
                </div>
            </div>
        </div>
    </template>
</div>
