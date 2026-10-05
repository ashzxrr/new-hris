@php
    $activeClass = $active ?? false ? 'pagination-btn active' : 'pagination-btn';
@endphp

<button 
    type="button" 
    class="{{ $activeClass }} {{ $class ?? '' }}"
    {{ $attributes->except('active', 'class') }}>
    {{ $slot }}
</button>
