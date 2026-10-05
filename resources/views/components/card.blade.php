@php
    $variantClasses = match($variant ?? 'default') {
        'dark' => 'bg-[#2F4156] text-white',
        default => 'bg-white text-[#2F4156]',
    };
    
    $accentClasses = match($accent ?? null) {
        'teal' => 'border-l-4 border-l-[#567C8D] rounded-l-none',
        'navy' => 'border-l-4 border-l-[#2F4156] rounded-l-none',
        default => '',
    };
    
    $shadowClass = $variant === 'dark' ? '' : 'shadow-[0_1px_4px_rgba(47,65,86,.06)]';
@endphp

<div class="rounded-[11px] border {{ $variant === 'dark' ? 'border-[#3F6070]' : 'border-[#C8D9E6]' }} p-4 {{ $variantClasses }} {{ $accentClasses }} {{ $shadowClass }}">
    {{-- Title Section --}}
    @if($title ?? null)
        <div class="flex items-center justify-between mb-3">
            <h3 class="{{ $variant === 'dark' ? 'text-[#C8D9E6]' : 'text-[#2F4156]' }} text-[13px] font-medium">
                {{ $title }}
            </h3>
            @if(isset($slot) && $slot->isNotEmpty())
                {{-- Check if there's a named slot for action --}}
                <div class="text-[11px] {{ $variant === 'dark' ? 'text-[#8BAFC4]' : 'text-[#567C8D]' }}">
                    {{ $slot }}
                </div>
            @endif
        </div>
    @endif

    {{-- Content --}}
    <div>
        {{ $content ?? $slot }}
    </div>
</div>
