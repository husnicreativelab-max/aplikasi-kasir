@props(['variant' => 'neutral'])
@php
    $variants = [
        'success' => 'bg-success text-ink', 'danger' => 'bg-danger text-ink',
        'warning' => 'bg-warning text-ink', 'info' => 'bg-info text-ink',
        'sky' => 'bg-sky text-ink', 'primary' => 'bg-primary text-ink',
        'neutral' => 'bg-white text-ink', 'dark' => 'bg-ink text-white',
        'muted' => 'bg-gray-100 text-ink',
    ];
@endphp
<span {{ $attributes->class('status-badge '.($variants[$variant] ?? $variants['neutral'])) }}>{{ $slot }}</span>
