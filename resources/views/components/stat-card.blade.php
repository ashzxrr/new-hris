@php
    $badgeClasses = match($badgeType ?? 'info') {
        'success' => 'bg-[#E0F2EA] text-[#1B7A4A]',
        'warn' => 'bg-[#FFF3DC] text-[#9A6200]',
        default => 'bg-[#EAF1F6] text-[#2F4156]',
    };
@endphp

<div class="bg-white rounded-[11px] border border-[#C8D9E6] p-4 shadow-[0_1px_4px_rgba(47,65,86,.06)]">
    {{-- Label --}}
    <p class="text-[11px] text-[#567C8D] mb-1">{{ $label }}</p>
    
    {{-- Value --}}
    <p class="text-[22px] font-medium text-[#2F4156] mb-2">{{ $value }}</p>
    
    {{-- Badge (jika ada) --}}
    @if($badge ?? null)
        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium {{ $badgeClasses }}">
            {{ $badge }}
        </span>
    @endif
</div>
