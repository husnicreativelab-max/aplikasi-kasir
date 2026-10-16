@props(['stock', 'minimum', 'unit' => 'pcs'])
@php
    $stockNumber = (float) $stock;
    $minimumNumber = max((float) $minimum, 1);
    $percent = min(100, max(0, (int) round(($stockNumber / $minimumNumber) * 100)));
    $tone = $stockNumber <= 0 ? 'bg-danger' : ($stockNumber <= (float) $minimum ? 'bg-warning' : 'bg-success');
    $label = $stockNumber <= 0 ? 'Habis' : ($stockNumber <= (float) $minimum ? 'Menipis' : 'Aman');
    $formattedStock = rtrim(rtrim(number_format($stockNumber, 3, ',', '.'), '0'), ',');
@endphp
<div {{ $attributes }}>
    <div class="mb-1 flex items-center justify-between gap-2 text-xs font-bold"><span>{{ $formattedStock }} {{ $unit }}</span><span>{{ $label }}</span></div>
    <div class="h-3 overflow-hidden rounded-pill border-2 border-ink bg-white" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Stok {{ $label }}">
        <div class="h-full {{ $tone }}" style="width: {{ $percent }}%"></div>
    </div>
</div>
