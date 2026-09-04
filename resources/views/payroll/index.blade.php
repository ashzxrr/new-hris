@extends('layouts.app')
@section('content')
<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-[#2F4156]">Payroll Harian</h1>
            <p class="text-xs text-[#567C8D] mt-1">Kelola payroll karyawan harian per periode</p>
        </div>
        <a href="{{ route('payroll.create') }}"
            class="pbtn pbtn-primary">
            <span class="pbtn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </span>
            <span>Buat Payroll Baru</span>
        </a>
    </div>

    {{-- Empty State --}}
    @if($payrolls->isEmpty())
    <div class="bg-white rounded-[11px] border border-[#C8D9E6] p-12 text-center shadow-[0_1px_4px_rgba(47,65,86,.06)]">
        <div class="text-4xl mb-3">💰</div>
        <p class="text-[#567C8D] text-sm">Belum ada payroll. Buat payroll pertama!</p>
        <a href="{{ route('payroll.create') }}"
            class="inline-block mt-4 pbtn pbtn-primary">
            <span class="pbtn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </span>
            <span>Buat Payroll</span>
        </a>
    </div>
    @else
    {{-- Cards Grid: 3 kolom agar setiap kartu lebih lega --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($payrolls as $p)
        <div class="bg-white rounded-[10px] border border-[#C8D9E6] px-4 py-4 flex flex-col
            {{ $p->status === 'final' ? 'ring-1 ring-[#1B7A4A]/30' : '' }}">
            {{-- Card Header with Status --}}
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-[13px] font-medium text-[#2F4156] truncate">{{ $p->periode }}</h3>
                @if($p->status === 'final')
                    <span class="shrink-0 rounded-full bg-[#E0F2EA] px-2 py-0.5 text-[10px] font-medium text-[#1B7A4A]">Final</span>
                @else
                    <span class="shrink-0 rounded-full bg-[#FFF3DC] px-2 py-0.5 text-[10px] font-medium text-[#9A6200]">Draft</span>
                @endif
            </div>

            @php
                $totalKaryawan = $p->grand_totals_count ?: $p->pengajuans_count ?: ($p->details_count ?? 0);
                $totalGaji = $p->total_gaji_gabungan;
                $belumDiproses = $totalKaryawan > 0 && (float) $totalGaji === 0.0;
                $formatRupiah = function ($nominal) {
                    return 'Rp ' . number_format((float) $nominal, 0, ',', '.');
                };
            @endphp

            {{-- Hero Metric --}}
            <div class="mt-2 flex items-baseline justify-between gap-2">
                <span class="text-[20px] font-bold leading-none text-[#2F4156]">{{ $totalKaryawan }}</span>
                @if($belumDiproses)
                    <span class="text-[11px] font-medium italic text-[#8BAFC4]">Belum diproses</span>
                @else
                    <span class="truncate text-right text-[14px] font-semibold leading-none text-[#2F4156]">{{ $formatRupiah($totalGaji) }}</span>
                @endif
            </div>
            <div class="mt-1 text-[10px] leading-none text-[#8BAFC4]">karyawan · total gaji</div>

            {{-- Category Breakdown --}}
            <div class="mt-3 grid grid-cols-2 gap-1.5">
                <div class="rounded-[5px] bg-[#F9FBFD] px-2 py-1.5">
                    <div class="text-[9px] font-medium uppercase tracking-[0.04em] text-[#8BAFC4]">Harian</div>
                    <div class="mt-0.5 truncate text-[11px] font-semibold leading-tight text-[#2F4156]">{{ $formatRupiah($p->total_harian) }}</div>
                </div>
                <div class="rounded-[5px] bg-[#F9FBFD] px-2 py-1.5">
                    <div class="text-[9px] font-medium uppercase tracking-[0.04em] text-[#8BAFC4]">Cabut</div>
                    <div class="mt-0.5 truncate text-[11px] font-semibold leading-tight text-[#2F4156]">{{ $formatRupiah($p->total_cabut) }}</div>
                </div>
                <div class="rounded-[5px] bg-[#F9FBFD] px-2 py-1.5">
                    <div class="text-[9px] font-medium uppercase tracking-[0.04em] text-[#8BAFC4]">HCR</div>
                    <div class="mt-0.5 truncate text-[11px] font-semibold leading-tight text-[#2F4156]">{{ $formatRupiah($p->total_hcr) }}</div>
                </div>
                <div class="rounded-[5px] bg-[#F9FBFD] px-2 py-1.5">
                    <div class="text-[9px] font-medium uppercase tracking-[0.04em] text-[#8BAFC4]">Moulding</div>
                    <div class="mt-0.5 truncate text-[11px] font-semibold leading-tight text-[#2F4156]">{{ $formatRupiah($p->total_moulding) }}</div>
                </div>
            </div>

            {{-- Card Footer with Actions --}}
            <div class="mt-3 flex items-center gap-2">
                <a href="{{ route('payroll.show', $p->id) }}"
                    class="flex-1 justify-center pbtn pbtn-secondary !h-[30px] !min-h-0 !rounded-[6px] !px-3 !text-[11px] !leading-none">
                    {{ $p->status === 'final' ? 'Lihat detail' : 'Detail' }}
                </a>
                @if($p->status === 'draft')
                <form method="POST" action="{{ route('payroll.destroy', $p->id) }}" class="min-w-0 flex-1"
                    onsubmit="return confirm('Hapus payroll ini?')">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="w-full pbtn pbtn-danger !h-[30px] !min-h-0 !rounded-[6px] !text-[11px]">
                        Hapus
                    </button>
                </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection