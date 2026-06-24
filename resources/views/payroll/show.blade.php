@extends('layouts.app')
@section('content')
<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Detail Payroll — {{ $payroll->periode }}</h1>
            <p class="text-xs text-slate-400 mt-1">
                {{ \Carbon\Carbon::parse($payroll->tanggal_dari)->format('d M Y') }} —
                {{ \Carbon\Carbon::parse($payroll->tanggal_sampai)->format('d M Y') }}
                <span class="ml-2">
                    @if($payroll->status === 'final')
                        <span class="bg-[#22C55E]/10 text-[#22C55E] px-2 py-0.5 rounded-full text-xs font-medium">Final</span>
                    @else
                        <span class="bg-[#F59E0B]/10 text-[#F59E0B] px-2 py-0.5 rounded-full text-xs font-medium">Draft</span>
                    @endif
                </span>
            </p>
        </div>
        <a href="{{ route('payroll.index') }}"
            class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm hover:bg-slate-50 transition">
            ← Kembali
        </a>
    </div>

    {{-- Grid Kartu Jenis --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Kartu Cabut --}}
        <div class="bg-white rounded-2xl border border-[#E5E7EB] p-5">
            <h3 class="font-semibold text-slate-800 mb-4">Cabut</h3>
            
            @if($cabutImport === null)
                <div class="text-center py-6">
                    <p class="text-sm text-slate-400 mb-3">Belum ada data</p>
                    <a href="{{ route('borongan.create', ['payroll_id' => $payroll->id, 'jenis' => 'cabut']) }}"
                        class="inline-block bg-[#4F46E5] text-white px-3 py-2 rounded-lg text-xs hover:bg-[#4338CA] transition">
                        + Upload
                    </a>
                </div>
            @else
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">File</p>
                        <p class="text-sm font-medium text-slate-700 truncate">{{ $cabutImport->filename }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Status</p>
                        <div class="flex items-center gap-2">
                            @if($cabutImport->status === 'approved')
                                <span class="inline-block bg-[#22C55E]/10 text-[#22C55E] px-2 py-1 rounded-full text-xs font-medium">Approved</span>
                            @elseif($cabutImport->status === 'reviewed')
                                <span class="inline-block bg-[#F59E0B]/10 text-[#F59E0B] px-2 py-1 rounded-full text-xs font-medium">Reviewed</span>
                            @else
                                <span class="inline-block bg-slate-100 text-slate-600 px-2 py-1 rounded-full text-xs font-medium">Pending</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-2 pt-3">
                        <a href="{{ route('borongan.review', $cabutImport->id) }}"
                            class="flex-1 text-center border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-xs hover:bg-slate-50 transition">
                            Review
                        </a>
                        <a href="{{ route('borongan.rekapIndex', $cabutImport->id) }}"
                            class="flex-1 text-center border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-xs hover:bg-slate-50 transition">
                            Rekap
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Kartu HCR/Cetak --}}
        <div class="bg-white rounded-2xl border border-[#E5E7EB] p-5">
            <h3 class="font-semibold text-slate-800 mb-4">HCR / Cetak</h3>
            
            @if($hcrImport === null)
                <div class="text-center py-6">
                    <p class="text-sm text-slate-400 mb-3">Belum ada data</p>
                    <a href="{{ route('borongan.create', ['payroll_id' => $payroll->id, 'jenis' => 'cetak']) }}"
                        class="inline-block bg-[#4F46E5] text-white px-3 py-2 rounded-lg text-xs hover:bg-[#4338CA] transition">
                        + Upload
                    </a>
                </div>
            @else
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">File</p>
                        <p class="text-sm font-medium text-slate-700 truncate">{{ $hcrImport->filename }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Status</p>
                        <div class="flex items-center gap-2">
                            @if($hcrImport->status === 'approved')
                                <span class="inline-block bg-[#22C55E]/10 text-[#22C55E] px-2 py-1 rounded-full text-xs font-medium">Approved</span>
                            @elseif($hcrImport->status === 'reviewed')
                                <span class="inline-block bg-[#F59E0B]/10 text-[#F59E0B] px-2 py-1 rounded-full text-xs font-medium">Reviewed</span>
                            @else
                                <span class="inline-block bg-slate-100 text-slate-600 px-2 py-1 rounded-full text-xs font-medium">Pending</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-2 pt-3">
                        <a href="{{ route('borongan.review', $hcrImport->id) }}"
                            class="flex-1 text-center border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-xs hover:bg-slate-50 transition">
                            Review
                        </a>
                        <a href="{{ route('borongan.rekapIndex', $hcrImport->id) }}"
                            class="flex-1 text-center border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-xs hover:bg-slate-50 transition">
                            Rekap
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Kartu Moulding --}}
        <div class="bg-white rounded-2xl border border-[#E5E7EB] p-5">
            <h3 class="font-semibold text-slate-800 mb-4">Moulding</h3>
            
            @if($mouldingImport === null)
                <div class="text-center py-6">
                    <p class="text-sm text-slate-400 mb-3">Belum ada data</p>
                    <a href="{{ route('borongan.create', ['payroll_id' => $payroll->id, 'jenis' => 'moulding']) }}"
                        class="inline-block bg-[#4F46E5] text-white px-3 py-2 rounded-lg text-xs hover:bg-[#4338CA] transition">
                        + Upload
                    </a>
                </div>
            @else
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">File</p>
                        <p class="text-sm font-medium text-slate-700 truncate">{{ $mouldingImport->filename }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Status</p>
                        <div class="flex items-center gap-2">
                            @if($mouldingImport->status === 'approved')
                                <span class="inline-block bg-[#22C55E]/10 text-[#22C55E] px-2 py-1 rounded-full text-xs font-medium">Approved</span>
                            @elseif($mouldingImport->status === 'reviewed')
                                <span class="inline-block bg-[#F59E0B]/10 text-[#F59E0B] px-2 py-1 rounded-full text-xs font-medium">Reviewed</span>
                            @else
                                <span class="inline-block bg-slate-100 text-slate-600 px-2 py-1 rounded-full text-xs font-medium">Pending</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex gap-2 pt-3">
                        <a href="{{ route('borongan.review', $mouldingImport->id) }}"
                            class="flex-1 text-center border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-xs hover:bg-slate-50 transition">
                            Review
                        </a>
                        <a href="{{ route('borongan.rekapIndex', $mouldingImport->id) }}"
                            class="flex-1 text-center border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-xs hover:bg-slate-50 transition">
                            Rekap
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Kartu Harian --}}
        <div class="bg-white rounded-2xl border border-[#E5E7EB] p-5">
            <h3 class="font-semibold text-slate-800 mb-4">Harian</h3>
            
            @if($harianDetailCount === 0)
                <div class="text-center py-6">
                    <p class="text-sm text-slate-400 mb-3">Belum ditarik</p>
                    <a href="{{ route('payroll.harian.show', $payroll->id) }}"
                        class="inline-block bg-[#4F46E5] text-white px-3 py-2 rounded-lg text-xs hover:bg-[#4338CA] transition">
                        Tarik Absensi
                    </a>
                </div>
            @else
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Karyawan</p>
                        <p class="text-lg font-bold text-slate-800">{{ $harianDetailCount }}</p>
                        <p class="text-xs text-slate-400">karyawan</p>
                    </div>
                    <a href="{{ route('payroll.harian.show', $payroll->id) }}"
                        class="block w-full text-center border border-[#4F46E5] text-[#4F46E5] px-3 py-2 rounded-lg text-xs hover:bg-indigo-50 transition font-medium">
                        Lihat Detail
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Grand Total Section --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Grand Total</h2>
        <div class="text-center py-8">
            <p class="text-sm text-slate-500 mb-4">Grand Total akan tersedia setelah semua jenis di-approve</p>
            <button type="button"
                disabled
                class="bg-slate-200 text-slate-400 px-4 py-2 rounded-lg text-sm cursor-not-allowed">
                Generate Grand Total
            </button>
        </div>
    </div>
</div>
@endsection
