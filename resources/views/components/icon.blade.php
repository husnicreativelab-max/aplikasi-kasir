@props(['name'])
@php($common = 'fill-none stroke-current stroke-[2.25]')
<svg {{ $attributes->class('inline-block shrink-0 '.$common) }} viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('dashboard') <rect x="3" y="3" width="8" height="8" rx="1"/><rect x="13" y="3" width="8" height="5" rx="1"/><rect x="13" y="10" width="8" height="11" rx="1"/><rect x="3" y="13" width="8" height="8" rx="1"/> @break
        @case('cashier') <path d="M4 7h16v13H4z"/><path d="M7 7V5a5 5 0 0 1 10 0v2M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"/> @break
        @case('box') <path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="M3 8v9l9 5 9-5V8M12 13v9"/> @break
        @case('tool') <path d="M14.5 6.5a5 5 0 0 0-6.8 6.8l-4.9 4.9a2 2 0 0 0 2.8 2.8l4.9-4.9a5 5 0 0 0 6.8-6.8l-3 3-2.8-2.8 3-3Z"/><path d="m18 3 3 3"/> @break
        @case('chart') <path d="M4 19V5M4 19h17"/><path d="m7 15 4-4 3 2 6-7"/><path d="M16 6h4v4"/> @break
        @case('settings') <circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.2.9-1.2 2.1-1.4-.5a7.8 7.8 0 0 1-1.5.9l-.2 1.5h-2.4l-.2-1.5a7.8 7.8 0 0 1-1.5-.9l-1.4.5-1.2-2.1 1.2-.9a7 7 0 0 1 0-1.8l-1.2-.9 1.2-2.1 1.4.5a7.8 7.8 0 0 1 1.5-.9l.2-1.5h2.4l.2 1.5a7.8 7.8 0 0 1 1.5.9l1.4-.5 1.2 2.1-1.2.9a7 7 0 0 1 0 1.8Z"/> @break
        @case('search') <circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5"/> @break
        @case('barcode') <path d="M4 5v14M7 5v14M10 5v14M14 5v14M17 5v14M20 5v14"/><path d="M2 3h20M2 21h20"/> @break
        @case('plus') <path d="M12 5v14M5 12h14"/> @break
        @case('minus') <path d="M5 12h14"/> @break
        @case('trash') <path d="M4 7h16M10 11v6M14 11v6M5 7l1 14h12l1-14M9 7V4h6v3"/> @break
        @case('print') <path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v7H6zM18 12h.01"/> @break
        @case('user') <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/> @break
        @case('logout') <path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/> @break
        @case('check') <path d="m5 12 4 4L19 6"/> @break
        @case('alert') <path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 9v5M12 17h.01"/> @break
        @case('star') <path d="m12 3 2.7 5.6 6.2.9-4.5 4.4 1.1 6.2-5.5-2.9-5.5 2.9 1.1-6.2L3.1 9.5l6.2-.9L12 3Z"/> @break
        @case('close') <path d="m6 6 12 12M18 6 6 18"/> @break
        @case('receipt') <path d="M5 3h14v18l-3-2-4 2-4-2-3 2V3Z"/><path d="M8 8h8M8 12h8M8 16h4"/> @break
        @case('wallet') <path d="M4 6h15a2 2 0 0 1 2 2v12H4a2 2 0 0 1-2-2V6a3 3 0 0 1 3-3h13"/><path d="M16 13h5M17 13h.01"/> @break
        @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/> @break
        @case('message') <path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5 7.8 7.8 0 0 1-3.2-.7L4 20l1.7-4.2a7.5 7.5 0 1 1 14.3-4.3Z"/><path d="M8.5 11.5h.01M12 11.5h.01M15.5 11.5h.01"/> @break
        @default <circle cx="12" cy="12" r="9"/><path d="M12 8h.01M11 12h1v4h1"/>
    @endswitch
</svg>
