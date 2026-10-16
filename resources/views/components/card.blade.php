@props(['title' => null, 'icon' => null, 'tone' => 'primary'])
<section {{ $attributes->class('brutal-card overflow-hidden') }}>
    @if($title)
        <header class="flex items-center gap-3 border-b-2 border-ink bg-cream px-4 py-3">
            @if($icon)
                <span class="grid h-10 w-10 place-items-center rounded-button border-2 border-ink bg-{{ $tone }} shadow-brutal-sm">
                    <x-icon :name="$icon" class="h-5 w-5" />
                </span>
            @endif
            <h2 class="text-lg font-extrabold">{{ $title }}</h2>
        </header>
    @endif
    <div class="p-4 sm:p-5">{{ $slot }}</div>
</section>
