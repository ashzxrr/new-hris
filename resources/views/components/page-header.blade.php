<div class="mb-6">
    {{-- Breadcrumb --}}
    @if($breadcrumb ?? null)
        <nav class="flex items-center gap-1.5 text-[11px] text-[#8BAFC4] mb-2">
            @foreach($breadcrumb as $index => $crumb)
                @if($index > 0)
                    <span>/</span>
                @endif
                @if($crumb['url'] ?? null)
                    <a href="{{ $crumb['url'] }}" class="hover:text-[#567C8D] transition">{{ $crumb['label'] }}</a>
                @else
                    <span class="text-[#567C8D] font-medium">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        </nav>
    @endif

    {{-- Title Row --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-[18px] font-semibold text-[#2F4156]">{{ $title }}</h1>
            @if($subtitle ?? null)
                <p class="text-[12px] text-[#567C8D] mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
        @if($actions ?? null)
            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
