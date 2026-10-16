<div class="lg:hidden" x-data="{ menuOpen: false }">
    <div x-show="menuOpen" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-ink/40" @click="menuOpen = false"></div>
    <div x-show="menuOpen" x-cloak x-transition class="fixed bottom-[74px] left-3 right-3 z-50 rounded-card border-2 border-ink bg-white p-3 shadow-brutal" aria-label="Menu lainnya">
        <div class="grid grid-cols-2 gap-2">
            @if(auth()->user()->isAdmin())<a class="nav-link" href="{{ route('goods-in.index') }}"><x-icon name="plus" class="h-5 w-5"/>Barang Masuk</a>@endif
            <a class="nav-link" href="{{ route('services.index') }}"><x-icon name="tool" class="h-5 w-5"/>Service</a>
            @if(auth()->user()->isAdmin())
                <a class="nav-link" href="{{ route('reports.index') }}"><x-icon name="chart" class="h-5 w-5"/>Laporan</a>
                <a class="nav-link" href="{{ route('settings.edit') }}"><x-icon name="settings" class="h-5 w-5"/>Pengaturan</a>
                <a class="nav-link" href="{{ route('categories.index') }}"><x-icon name="box" class="h-5 w-5"/>Kategori</a>
                <a class="nav-link" href="{{ route('users.index') }}"><x-icon name="user" class="h-5 w-5"/>Pengguna</a>
            @endif
        </div>
    </div>
    <nav class="fixed inset-x-0 bottom-0 z-40 grid min-h-[70px] grid-cols-4 items-center border-t-[3px] border-ink bg-cream px-2 pb-[env(safe-area-inset-bottom)]" aria-label="Navigasi bawah">
        <a href="{{ route('dashboard') }}" class="mx-auto flex min-h-12 w-full flex-col items-center justify-center gap-0.5 rounded-button text-xs font-bold {{ request()->routeIs('dashboard') ? 'bg-primary' : '' }}"><x-icon name="dashboard" class="h-5 w-5"/>Home</a>
        <a href="{{ route('products.index') }}" class="mx-auto flex min-h-12 w-full flex-col items-center justify-center gap-0.5 rounded-button text-xs font-bold {{ request()->routeIs('products.*') ? 'bg-primary' : '' }}"><x-icon name="box" class="h-5 w-5"/>Produk</a>
        <a href="{{ route('pos.index') }}" class="mx-auto -mt-5 grid h-[58px] w-[58px] place-items-center rounded-[18px] border-[3px] border-ink bg-primary text-xs font-extrabold shadow-brutal" aria-label="Buka Kasir"><span class="flex flex-col items-center gap-0.5"><x-icon name="cashier" class="h-5 w-5"/>Kasir</span></a>
        <button type="button" @click="menuOpen = !menuOpen" class="mx-auto flex min-h-12 w-full flex-col items-center justify-center gap-0.5 rounded-button text-xs font-bold" :aria-expanded="menuOpen"><x-icon name="settings" class="h-5 w-5"/>Menu</button>
    </nav>
</div>
