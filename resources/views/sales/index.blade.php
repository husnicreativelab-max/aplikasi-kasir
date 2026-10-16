@extends('layouts.app')
@section('title', 'Riwayat transaksi')
@section('content')
<div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-end"><div><p class="text-sm font-bold text-ink/60">Struk penjualan dan pembatalan</p><h1 class="text-3xl font-extrabold">Riwayat transaksi</h1><p class="mt-1 text-sm text-ink/70">{{ auth()->user()->isAdmin() ? 'Admin dapat mencari semua transaksi dan memproses pembatalan / retur penuh.' : 'Riwayat transaksi yang Anda proses.' }}</p></div><x-button as="a" href="{{ route('pos.index') }}" variant="primary"><x-icon name="cashier" class="h-5 w-5"/>Buka kasir</x-button></div>

<form method="GET" action="{{ route('sales.index') }}" class="brutal-card mb-5 grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_170px_170px_170px_auto] xl:items-end">
    <x-field label="Nomor transaksi" name="q"><input id="q" name="q" type="search" value="{{ request('q') }}" class="brutal-input" placeholder="INV-20261016-0001"></x-field>
    <x-field label="Dari tanggal" name="from"><input id="from" name="from" type="date" value="{{ request('from') }}" class="brutal-input"></x-field>
    <x-field label="Sampai tanggal" name="to"><input id="to" name="to" type="date" value="{{ request('to') }}" class="brutal-input"></x-field>
    <x-field label="Status" name="status"><select id="status" name="status" class="brutal-input"><option value="">Semua</option><option value="completed" @selected(request('status') === 'completed')>Selesai</option><option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan / retur</option></select></x-field>
    <x-button variant="neutral" type="submit"><x-icon name="search" class="h-5 w-5"/>Cari</x-button>
</form>

<div class="hidden overflow-hidden rounded-card border-2 border-ink bg-white shadow-brutal md:block"><div class="overflow-x-auto"><table class="w-full min-w-[700px] text-sm"><thead class="table-head"><tr><th class="px-4 py-3">Nomor / tanggal</th><th class="px-4 py-3">Kasir</th><th class="px-4 py-3">Pembayaran</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Status</th><th class="px-4 py-3"></th></tr></thead><tbody>
    @forelse($sales as $sale)
        <tr class="border-t border-ink/20 hover:bg-cream/60"><td class="px-4 py-3"><p class="font-extrabold">{{ $sale->number }}</p><p class="text-xs text-ink/60">{{ $sale->sold_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p></td><td class="px-4 py-3">{{ $sale->user?->name ?? 'Akun dihapus' }}</td><td class="px-4 py-3">{{ ucfirst($sale->payment_method) }}</td><td class="px-4 py-3 font-extrabold">Rp {{ number_format($sale->total, 0, ',', '.') }}</td><td class="px-4 py-3"><x-badge :variant="$sale->status === 'completed' ? 'success' : 'danger'">{{ $sale->status === 'completed' ? 'Selesai' : 'Dibatalkan / retur' }}</x-badge></td><td class="px-4 py-3"><x-button as="a" href="{{ route('sales.receipt', $sale) }}" variant="info" size="sm">Buka struk</x-button></td></tr>
    @empty
        <tr><td colspan="6" class="px-4 py-12 text-center"><x-icon name="receipt" class="mx-auto h-8 w-8"/><p class="mt-2 font-extrabold">Belum ada transaksi</p><p class="text-sm text-ink/65">Transaksi baru akan muncul di sini.</p></td></tr>
    @endforelse
</tbody></table></div>@if($sales->hasPages())<div class="border-t-2 border-ink bg-cream p-3">{{ $sales->links() }}</div>@endif</div>

<div class="space-y-3 md:hidden">
    @forelse($sales as $sale)
        <article class="brutal-card p-4"><div class="flex items-start justify-between gap-2"><div><p class="font-extrabold">{{ $sale->number }}</p><p class="text-xs text-ink/60">{{ $sale->sold_at->timezone(config('app.timezone'))->translatedFormat('d M Y, H:i') }} WIB</p></div><x-badge :variant="$sale->status === 'completed' ? 'success' : 'danger'">{{ $sale->status === 'completed' ? 'Selesai' : 'Retur' }}</x-badge></div><div class="mt-3 flex items-end justify-between gap-2 border-t border-ink/15 pt-3"><div><p class="text-xs text-ink/60">{{ $sale->user?->name ?? 'Akun dihapus' }} · {{ ucfirst($sale->payment_method) }}</p><p class="text-lg font-extrabold">Rp {{ number_format($sale->total, 0, ',', '.') }}</p></div><x-button as="a" href="{{ route('sales.receipt', $sale) }}" variant="info" size="sm">Buka struk</x-button></div></article>
    @empty
        <div class="brutal-card p-8 text-center"><x-icon name="receipt" class="mx-auto h-8 w-8"/><p class="mt-2 font-extrabold">Belum ada transaksi</p></div>
    @endforelse
    @if($sales->hasPages())<div class="rounded-card border-2 border-ink bg-white p-3">{{ $sales->links() }}</div>@endif
</div>
@endsection
