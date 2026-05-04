@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-mono text-sm text-live']) }}>
        {{ $status }}
    </div>
@endif
