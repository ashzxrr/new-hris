@php
    $activeClass = $active ?? false ? 'tab-btn active' : 'tab-btn';
@endphp

<button 
    type="button" 
    class="{{ $activeClass }} {{ $class ?? '' }}"
    {{ $attributes->except('active', 'class') }}>
    {{ $slot }}
</button>
