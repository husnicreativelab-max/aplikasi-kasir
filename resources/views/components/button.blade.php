@props([
    'variant' => 'neutral',
    'type' => 'button',
    'as' => 'button',
    'href' => null,
    'size' => 'md',
])
@php
    $variants = [
        'primary' => 'bg-primary',
        'success' => 'bg-success',
        'danger' => 'bg-danger',
        'info' => 'bg-info',
        'warning' => 'bg-warning',
        'sky' => 'bg-sky',
        'neutral' => 'bg-white',
        'dark' => 'bg-ink text-white',
    ];
    $sizes = ['sm' => 'min-h-11 px-3 text-sm', 'md' => 'min-h-12 px-4', 'lg' => 'min-h-14 px-6 text-lg'];
    $classes = 'brutal-btn '.($variants[$variant] ?? $variants['neutral']).' '.($sizes[$size] ?? $sizes['md']);
@endphp
@if($as === 'a' || $href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
