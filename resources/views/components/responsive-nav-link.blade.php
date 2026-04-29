@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-accent text-start text-base font-mono font-medium text-text bg-panel focus:outline-none focus:text-text focus:bg-panel focus:border-accent transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-mono font-medium text-muted hover:text-label hover:bg-panel hover:border-border focus:outline-none focus:text-label focus:bg-panel focus:border-border transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>