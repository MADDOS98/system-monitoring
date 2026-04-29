@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-panel border border-border text-text font-mono text-sm rounded-md placeholder-muted focus:border-accent focus:ring-1 focus:ring-accent focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed transition duration-150 ease-in-out']) }}>