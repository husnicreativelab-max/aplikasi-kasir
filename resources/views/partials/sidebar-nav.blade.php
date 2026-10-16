<nav class="flex-1 space-y-1" aria-label="Menu utama">
    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}"><x-icon name="dashboard" class="h-5 w-5"/>Dashboard</a>
    <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.*') || (request()->routeIs('sales.*') && !request()->routeIs('sales.index')) ? 'nav-link-active' : '' }}"><x-icon name="cashier" class="h-5 w-5"/>Kasir</a>
    <a href="{{ route('sales.index') }}" class="nav-link {{ request()->routeIs('sales.index') ? 'nav-link-active' : '' }}"><x-icon name="receipt" class="h-5 w-5"/>Riwayat transaksi</a>
    <a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'nav-link-active' : '' }}"><x-icon name="box" class="h-5 w-5"/>Produk</a>
    @if(auth()->user()->isAdmin())<a href="{{ route('goods-in.index') }}" class="nav-link {{ request()->routeIs('goods-in.*') ? 'nav-link-active' : '' }}"><x-icon name="plus" class="h-5 w-5"/>Barang Masuk</a>@endif
    <a href="{{ route('services.index') }}" class="nav-link {{ request()->routeIs('services.*') ? 'nav-link-active' : '' }}"><x-icon name="tool" class="h-5 w-5"/>Service</a>
    @if(auth()->user()->isAdmin())
        <div class="mb-1 mt-5 border-t-2 border-ink pt-4"><p class="px-3 pb-2 text-[11px] font-extrabold uppercase tracking-widest text-ink/60">Pemilik</p></div>
        <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'nav-link-active' : '' }}"><x-icon name="chart" class="h-5 w-5"/>Laporan</a>
        <a href="{{ route('settings.edit') }}" class="nav-link {{ request()->routeIs('settings.*') || request()->routeIs('users.*') || request()->routeIs('categories.*') ? 'nav-link-active' : '' }}"><x-icon name="settings" class="h-5 w-5"/>Pengaturan</a>
    @endif
</nav>
