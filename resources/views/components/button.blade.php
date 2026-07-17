@php
    $variantClass = match($variant ?? 'primary') {
        'primary' => 'pbtn-primary',
        'secondary' => 'pbtn-secondary',
        'danger' => 'pbtn-danger',
        'success' => 'pbtn-success',
        'warning' => 'pbtn-warning',
        'ghost' => 'pbtn-ghost',
        default => 'pbtn-primary',
    };
    
    $sizeClass = match($size ?? 'default') {
        'sm' => 'pbtn-sm',
        'lg' => 'pbtn-lg',
        default => '',
    };
    
    $loadingClass = $loading ?? false ? 'is-loading' : '';
@endphp

<button 
    type="{{ $type ?? 'button' }}" 
    class="pbtn {{ $variantClass }} {{ $sizeClass }} {{ $loadingClass }} {{ $class ?? '' }}"
    {{ $loading ?? false ? 'disabled' : '' }}
    {{ $attributes->except('variant', 'size', 'icon', 'loading', 'class', 'label') }}>
    
    {{-- Icon (dalam lingkaran putih) --}}
    @if($icon ?? null)
        <span class="pbtn-icon">
            {!! $icon !!}
        </span>
    @endif

    {{-- Loading Spinner --}}
    @if($loading ?? false)
        <span class="pbtn-spinner"></span>
    @else
        {{-- Label --}}
        @if($label ?? null)
            <span>{{ $label }}</span>
        @else
            {{ $slot }}
        @endif
    @endif
</button>
