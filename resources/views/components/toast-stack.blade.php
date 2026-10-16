<div class="pointer-events-none fixed inset-x-3 top-3 z-[100] mx-auto flex max-w-lg flex-col gap-2 sm:inset-x-auto sm:right-5" aria-live="polite">
    @foreach(['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $variant)
        @if(session($key))
            <div x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 4500)" x-transition class="pointer-events-auto flex items-start gap-3 rounded-card border-2 border-ink bg-{{ $variant }} p-3 shadow-brutal" role="status">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border-2 border-ink bg-white"><x-icon :name="$key === 'error' ? 'alert' : 'check'" class="h-4 w-4" /></span>
                <p class="flex-1 pt-1 text-sm font-bold">{{ session($key) }}</p>
                <button type="button" class="grid h-11 w-11 place-items-center rounded-full border-2 border-ink bg-white" @click="visible = false" aria-label="Tutup notifikasi"><x-icon name="close" class="h-4 w-4" /></button>
            </div>
        @endif
    @endforeach
    @if($errors->any())
        <div x-data="{ visible: true }" x-show="visible" x-init="setTimeout(() => visible = false, 6500)" x-transition class="pointer-events-auto rounded-card border-2 border-ink bg-danger p-4 shadow-brutal" role="alert">
            <div class="flex items-start gap-2"><x-icon name="alert" class="mt-0.5 h-5 w-5"/><div><p class="font-extrabold">Periksa kembali data:</p><ul class="mt-1 list-inside list-disc text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
        </div>
    @endif
</div>
