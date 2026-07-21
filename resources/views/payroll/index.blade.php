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
    {{-- Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @foreach($payrolls as $p)
        <div class="bg-white rounded-[11px] border border-[#C8D9E6] overflow-hidden shadow-[0_1px_4px_rgba(47,65,86,.06)] hover:shadow-[0_4px_12px_rgba(47,65,86,.12)] transition-shadow flex flex-col">
            {{-- Card Header with Status --}}
            <div class="px-4 py-3 bg-[#EAF1F6] border-b border-[#C8D9E6] flex items-center justify-between">
                <h3 class="text-[13px] font-medium text-[#2F4156]">{{ $p->periode }}</h3>
                @if($p->status === 'final')
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-[#E0F2EA] text-[#1B7A4A] font-medium">Final</span>
                @else
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-[#FFF3DC] text-[#9A6200] font-medium">Draft</span>
                @endif
            </div>

            {{-- Card Content --}}
            <div class="px-4 py-4 flex-1">
                {{-- Tanggal --}}
                <div class="mb-4">
                    <p class="text-[11px] text-[#567C8D] mb-1">Periode Tanggal</p>
                    <p class="text-[12.5px] text-[#2F4156] font-medium">
                        {{ \Carbon\Carbon::parse($p->tanggal_dari)->format('d M Y') }} —
                        {{ \Carbon\Carbon::parse($p->tanggal_sampai)->format('d M Y') }}
                    </p>
                </div>

                {{-- Total Karyawan --}}
                <div class="mb-4 pb-4 border-b border-[#EAF1F6]">
                    @php
                        $totalKaryawan = $p->grand_totals_count ?: $p->pengajuans_count ?: ($p->details_count ?? 0);
                        $totalGaji = $p->grand_totals_sum_total_akhir ?: $p->pengajuans_sum_total_akhir ?: ($p->details_sum_total_gaji ?? 0);
                    @endphp
                    <p class="text-[11px] text-[#567C8D] mb-1">Total Karyawan</p>
                    <p class="text-[18px] font-semibold text-[#2F4156]">{{ $totalKaryawan }}</p>
                </div>

                {{-- Total Gaji --}}
                <div>
                    <p class="text-[11px] text-[#567C8D] mb-1">Total Gaji</p>
                    <p class="text-[16px] font-semibold text-[#2F4156]">
                        Rp {{ number_format($totalGaji, 0, ',', '.') }}
                    </p>
                </div>
            </div>

            {{-- Card Footer with Actions --}}
            <div class="px-4 py-3 bg-[#F9FBFD] border-t border-[#EAF1F6] flex items-center gap-2">
                <a href="{{ route('payroll.show', $p->id) }}"
                    class="flex-1 text-center pbtn pbtn-secondary pbtn-sm">
                    Lihat Detail
                </a>
                @if($p->status === 'draft')
                <form method="POST" action="{{ route('payroll.destroy', $p->id) }}" class="flex-1"
                    onsubmit="return confirm('Hapus payroll ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full pbtn pbtn-danger pbtn-sm text-[12px]">
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
