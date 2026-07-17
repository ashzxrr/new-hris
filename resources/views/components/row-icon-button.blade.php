@php
    $typeClass = match($type ?? 'default') {
        'danger' => 'row-icon-btn danger',
        'success' => 'row-icon-btn success',
        default => 'row-icon-btn',
    };
@endphp

<button 
    type="button" 
    class="{{ $typeClass }} {{ $class ?? '' }}"
    {{ $attributes->except('type', 'class') }}>
    {{ $slot }}
</button>
