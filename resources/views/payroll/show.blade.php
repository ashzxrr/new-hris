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
            class="pbtn pbtn-secondary">
            <span class="pbtn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            </span>
            <span>Kembali</span>
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
                        class="pbtn pbtn-primary pbtn-sm">
                        <span class="pbtn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                        </span>
                        <span>Upload</span>
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
                    <div class="flex flex-wrap gap-2 pt-3">
                        <a href="{{ route('borongan.review', $cabutImport->id) }}"
                            class="flex-1 pbtn pbtn-secondary pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </span>
                            <span>Review</span>
                        </a>
                        <a href="{{ route('borongan.rekapIndex', $cabutImport->id) }}"
                            class="flex-1 pbtn pbtn-secondary pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            </span>
                            <span>Rekap</span>
                        </a>
                        <a href="{{ route('borongan.create', ['payroll_id' => $payroll->id, 'jenis' => 'cabut', 'revisi' => 1]) }}"
                            class="flex-1 pbtn pbtn-warning pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            </span>
                            <span>Upload Revisi</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Kartu HCR --}}
        <div class="bg-white rounded-2xl border border-[#E5E7EB] p-5">
            <h3 class="font-semibold text-slate-800 mb-4">HCR</h3>
            
            @if($hcrImport === null)
                <div class="text-center py-6">
                    <p class="text-sm text-slate-400 mb-3">Belum ada data</p>
                    <a href="{{ route('borongan.create', ['payroll_id' => $payroll->id, 'jenis' => 'cetak']) }}"
                        class="pbtn pbtn-primary pbtn-sm">
                        <span class="pbtn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                        </span>
                        <span>Upload</span>
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
                    <div class="flex flex-wrap gap-2 pt-3">
                        <a href="{{ route('borongan.review', $hcrImport->id) }}"
                            class="flex-1 pbtn pbtn-secondary pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </span>
                            <span>Review</span>
                        </a>
                        <a href="{{ route('borongan.rekapIndex', $hcrImport->id) }}"
                            class="flex-1 pbtn pbtn-secondary pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            </span>
                            <span>Rekap</span>
                        </a>
                        <a href="{{ route('borongan.create', ['payroll_id' => $payroll->id, 'jenis' => 'cetak', 'revisi' => 1]) }}"
                            class="flex-1 pbtn pbtn-warning pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            </span>
                            <span>Upload Revisi</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Kartu Moulding/Cetak --}}
        <div class="bg-white rounded-2xl border border-[#E5E7EB] p-5">
            <h3 class="font-semibold text-slate-800 mb-4">Moulding/Cetak</h3>
            
            @if($mouldingImport === null)
                <div class="text-center py-6">
                    <p class="text-sm text-slate-400 mb-3">Belum ada data</p>
                    <a href="{{ route('borongan.create', ['payroll_id' => $payroll->id, 'jenis' => 'moulding']) }}"
                        class="pbtn pbtn-primary pbtn-sm">
                        <span class="pbtn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                        </span>
                        <span>Upload</span>
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
                    <div class="flex flex-wrap gap-2 pt-3">
                        <a href="{{ route('borongan.review', $mouldingImport->id) }}"
                            class="flex-1 pbtn pbtn-secondary pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </span>
                            <span>Review</span>
                        </a>
                        <a href="{{ route('borongan.rekapIndex', $mouldingImport->id) }}"
                            class="flex-1 pbtn pbtn-secondary pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            </span>
                            <span>Rekap</span>
                        </a>
                        <a href="{{ route('borongan.create', ['payroll_id' => $payroll->id, 'jenis' => 'moulding', 'revisi' => 1]) }}"
                            class="flex-1 pbtn pbtn-warning pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            </span>
                            <span>Upload Revisi</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>

        {{-- Kartu NKK --}}
        <div class="bg-white rounded-2xl border border-[#E5E7EB] p-5">
            <h3 class="font-semibold text-slate-800 mb-4">NKK</h3>
            
            @if($nkkImport === null)
                <div class="text-center py-6">
                    <p class="text-sm text-slate-400 mb-3">Belum ada data</p>
                    <a href="{{ route('borongan.create', ['payroll_id' => $payroll->id, 'jenis' => 'nkk']) }}"
                        class="pbtn pbtn-primary pbtn-sm">
                        <span class="pbtn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                        </span>
                        <span>Upload</span>
                    </a>
                </div>
            @else
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">File</p>
                        <p class="text-sm font-medium text-slate-700 truncate">{{ $nkkImport->filename }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Status</p>
                        <div class="flex items-center gap-2">
                            @if($nkkImport->status === 'approved')
                                <span class="inline-block bg-[#22C55E]/10 text-[#22C55E] px-2 py-1 rounded-full text-xs font-medium">Approved</span>
                            @elseif($nkkImport->status === 'reviewed')
                                <span class="inline-block bg-[#F59E0B]/10 text-[#F59E0B] px-2 py-1 rounded-full text-xs font-medium">Reviewed</span>
                            @else
                                <span class="inline-block bg-slate-100 text-slate-600 px-2 py-1 rounded-full text-xs font-medium">Pending</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-3">
                        <a href="{{ route('borongan.review', $nkkImport->id) }}"
                            class="flex-1 pbtn pbtn-secondary pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </span>
                            <span>Review</span>
                        </a>
                        <a href="{{ route('borongan.rekapIndex', $nkkImport->id) }}"
                            class="flex-1 pbtn pbtn-secondary pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                            </span>
                            <span>Rekap</span>
                        </a>
                        <a href="{{ route('borongan.create', ['payroll_id' => $payroll->id, 'jenis' => 'nkk', 'revisi' => 1]) }}"
                            class="flex-1 pbtn pbtn-warning pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            </span>
                            <span>Upload Revisi</span>
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
                    <button type="button" onclick="tarikAbsensiDirect(this)"
                        class="pbtn pbtn-secondary pbtn-sm inline-flex items-center justify-center gap-2">
                        <span class="pbtn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M4 4v6h6"/><path d="M20 20v-6h-6"/><path d="M5 9a7 7 0 0 1 11-5.6"/><path d="M19 15a7 7 0 0 1-11 5.6"/></svg>
                        </span>
                        <span>Tarik Data Absensi</span>
                    </button>
                </div>
            @else
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-slate-500 mb-1">Karyawan</p>
                        <p class="text-lg font-bold text-slate-800">{{ $harianDetailCount }}</p>
                        <p class="text-xs text-slate-400">karyawan</p>
                    </div>
                    <div class="flex flex-col gap-2">
                        <button type="button" onclick="tarikAbsensiDirect(this)"
                            class="w-full pbtn pbtn-secondary pbtn-sm inline-flex items-center justify-center gap-2 text-center">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M4 4v6h6"/><path d="M20 20v-6h-6"/><path d="M5 9a7 7 0 0 1 11-5.6"/><path d="M19 15a7 7 0 0 1-11 5.6"/></svg>
                            </span>
                            <span>Tarik Data Absensi</span>
                        </button>
                        <a href="{{ route('payroll.harian.show', $payroll->id) }}"
                            class="block w-full text-center border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-xs hover:bg-slate-50 transition font-medium">
                            Lihat Detail
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Tarik Data modal removed; Tarik Data now triggers directly on button click --}}

    {{-- Grand Total Section --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6" id="grandTotalSection">
        <div class="flex items-center justify-between mb-4 gap-3">
            <div>
                <h2 class="font-semibold text-slate-800">Grand Total</h2>
                <p class="text-xs text-slate-500 mt-1">Sembunyikan panel ini saat sedang tidak perlu dilihat.</p>
            </div>
            <div class="flex items-center gap-3">
                <button id="toggleGrandTotalBtn" type="button" class="pbtn pbtn-ghost pbtn-sm">
                    <span class="pbtn-icon" id="toggleGrandTotalBtnIcon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                    <span id="toggleGrandTotalBtnText">Sembunyikan Grand Total</span>
                </button>
                <form id="generateGrandTotalForm" method="POST" action="{{ route('payroll.generateGrandTotal', $payroll->id) }}" onsubmit="return confirmGenerateGrandTotal(event)">
                    @csrf
                    <input type="hidden" name="force" id="forceGrandTotal" value="0">
                    <button id="generateGrandTotalBtn" type="submit" class="pbtn pbtn-primary">
                        <span id="generateGrandTotalIcon" class="pbtn-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M3 3a1 1 0 011-1h4a1 1 0 110 2H5v12h10V4h-3a1 1 0 110-2h4a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V3z" /></svg>
                        </span>
                        <span id="generateGrandTotalBtnLabel">Generate Grand Total</span>
                    </button>
                </form>
            </div>
        </div>

        <div id="grandTotalBody" class="space-y-4 transition-all duration-300 ease-out">
            <p class="text-sm text-slate-500 mb-4">Grand Total akan tersedia setelah semua jenis di-approve</p>

        {{-- Controls: search + section filter + visible total --}}
        @php
            $sectionOptions = isset($grandTotals) ? $grandTotals->pluck('section')->unique()->filter()->values() : collect();
            $sectionLabels = ['cabut' => 'Cabut', 'hcr' => 'Titil Hcr', 'moulding' => 'Moulding', 'harian' => 'Harian', 'tambahan' => 'Tambahan'];
        @endphp
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <input id="searchGrandTotal" type="text" placeholder="Cari NIP atau Nama..."
                class="border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none w-64" />

            <select id="filterJob" class="px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
                <option value="">Semua Section</option>
                @foreach($sectionOptions as $sec)
                    <option value="{{ $sec }}">{{ $sectionLabels[$sec] ?? $sec }}</option>
                @endforeach
            </select>

            <div class="ml-auto text-sm text-slate-600">Total Terlihat: <span id="visibleGrandTotal" class="font-semibold">Rp {{ isset($grandTotals) ? number_format($grandTotals->sum('total_akhir'),0,',','.') : '0' }}</span></div>
        </div>

        <style>
            .nip-col { white-space: nowrap; width:1%; }
            .name-col { min-width: 160px; }
            .job-col { white-space: nowrap; max-width: 180px; }
            .date-col { white-space: nowrap; width:40px; text-align:center; }
            .money-col { white-space: nowrap; min-width:110px; text-align:right; }
            .hide-dates .date-col { display: none; }
        </style>

        @if(isset($grandTotals) && $grandTotals->isNotEmpty())
            @php $groups = $grandTotals->groupBy('section'); @endphp
            @php $groups = $grandTotals->groupBy('section'); @endphp

            <div class="flex items-center gap-3">
                <button id="toggleDatesBtn" type="button" class="pbtn pbtn-ghost pbtn-sm">Sembunyikan Tanggal</button>
            </div>

            <div id="grandtotal-groups" class="space-y-6">
                @foreach($groups as $section => $group)
                    <div class="grandtotal-section" data-section="{{ $section }}">
                        <div class="flex items-center justify-between mb-2 grandtotal-section-header cursor-pointer rounded-lg px-3 py-2 hover:bg-slate-50 transition" role="button" tabindex="0" aria-expanded="true">
                            <div class="flex items-center gap-2">
                                <span class="section-toggle-icon text-slate-400 transition-transform duration-200">▾</span>
                                <div class="text-sm font-semibold text-slate-700">REKAPITULASI BAGIAN {{ strtoupper($sectionLabels[$section] ?? $section) }}</div>
                            </div>
                            <div class="text-sm text-slate-500">Total Section: <span class="job-visible-total font-medium" data-section="{{ $section }}">Rp {{ number_format($group->sum('total_akhir'),0,',','.') }}</span></div>
                        </div>

                        <div class="overflow-x-auto grandtotal-section-body">
                            <table class="w-full text-sm">
                                <thead class="text-xs text-slate-500 uppercase tracking-wide bg-[#F8FAFC] border-b">
                                    <tr>
                                        <th class="nip-col px-3 py-2 text-left">NIP</th>
                                        <th class="name-col px-3 py-2 text-left">Nama</th>
                                        <th class="job-col px-3 py-2 text-left">Job</th>
                                        @php $dates = $periodeTanggal ?? [];
                                        @endphp
                                        @foreach($dates as $tanggal)
                                            <th class="date-col px-1 py-2 text-center text-xs" title="{{ \Carbon\Carbon::parse($tanggal)->format('d/m/Y') }}">{{ \Carbon\Carbon::parse($tanggal)->format('d') }}</th>
                                        @endforeach
                                        <th class="money-col px-3 py-2 text-right">Total Lembur</th>
                                        <th class="money-col px-3 py-2 text-right">Total Akhir</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group as $g)
                                    @php
                                        $detail = is_array($g->detail_harian) ? $g->detail_harian : json_decode($g->detail_harian, true) ?? [];
                                    @endphp
                                    <tr class="border-b border-[#E5E7EB]/50 hover:bg-[#F8FAFC] grandtotal-row" data-section="{{ $g->section }}" data-nip="{{ $g->nip }}" data-nama="{{ strtolower($g->nama) }}" data-total="{{ $g->total_akhir }}">
                                        <td class="nip-col px-3 py-2 font-mono text-xs">{{ $g->nip }}</td>
                                        <td class="name-col px-3 py-2 font-medium text-slate-800">{{ $g->nama }}</td>
                                        <td class="job-col px-3 py-2 text-slate-600">{{ $g->job_label }}</td>
                                        @foreach($dates as $tanggal)
                                            <td class="date-col px-1 py-1 text-right text-xs text-slate-600">Rp {{ number_format($detail[$tanggal] ?? 0, 0, ',', '.') }}</td>
                                        @endforeach
                                        <td class="money-col px-3 py-2 text-right text-slate-600">Rp {{ number_format($g->total_lembur ?? 0, 0, ',', '.') }}</td>
                                        <td class="money-col px-3 py-2 text-right font-bold">Rp {{ number_format($g->total_akhir ?? 0, 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-6">
                <p class="text-sm text-slate-400 mb-3">Belum ada Grand Total</p>
            </div>
        @endif
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6 mt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-800">Pengajuan & Pencairan</h3>
            <div class="flex gap-3">
                <form id="generatePengajuanForm" method="POST" action="{{ route('payroll.generatePengajuan', $payroll->id) }}" onsubmit="return handleGeneratePengajuan(event)" style="display:inline;">
                    @csrf
                    <button id="generatePengajuanBtn" type="submit" class="pbtn pbtn-secondary">
                        Generate Pengajuan
                    </button>
                </form>
                @if($sudahAdaPengajuan)
                    <a href="{{ route('payroll.exportPengajuan', $payroll->id) }}" class="pbtn pbtn-primary">
                        <span class="pbtn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                        </span>
                        <span>Export Excel</span>
                    </a>
                    <a href="{{ route('payroll.export.slip', $payroll->id) }}" class="pbtn pbtn-secondary">
                        <span class="pbtn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 19H9"/><path d="M15 19H12"/></svg>
                        </span>
                        <span>Export Slip</span>
                    </a>
                    <a href="{{ route('payroll.pengajuan', $payroll->id) }}" class="pbtn pbtn-secondary">
                        <span class="pbtn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 19H9"/><path d="M15 19H12"/></svg>
                        </span>
                        <span>Lihat Pengajuan</span>
                    </a>
                @else
                    <span title="Generate Pengajuan dulu" class="pbtn pbtn-secondary" style="opacity:0.5;cursor:not-allowed;">
                        <span class="pbtn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                        </span>
                        <span>Export Excel</span>
                    </span>
                    <span title="Generate Pengajuan dulu" class="pbtn pbtn-secondary" style="opacity:0.5;cursor:not-allowed;">
                        <span class="pbtn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 19H9"/><path d="M15 19H12"/></svg>
                        </span>
                        <span>Lihat Pengajuan</span>
                    </span>
                @endif
            </div>
        </div>
        <p class="text-xs text-slate-400">Generate Pengajuan akan mengambil data dari Grand Total yang sudah di-generate, digabung dengan data rekening dari Data Bank.</p>
    </div>
</div>

{{-- Loading Overlay --}}
<script>
    function handleGeneratePengajuan(e) {
        e.preventDefault();
        const confirmed = confirm('Generate Pengajuan dari Grand Total saat ini? Data lama akan ditimpa.');
        if (!confirmed) return false;

        const form = document.getElementById('generatePengajuanForm');
        form.submit();
        return false;
    }

    function confirmGenerateGrandTotal(e) {
        const form = document.getElementById('generateGrandTotalForm');

        @if(!$bisaGenerateGrandTotal)
            const lanjut = confirm('⚠️ Belum semua jenis di-approve / periode belum final.\n\nGenerate Grand Total sekarang tetap bisa dilakukan, tapi datanya mungkin belum lengkap. Lanjutkan?');
            if (!lanjut) return false;
            document.getElementById('forceGrandTotal').value = '1';
        @else
            if (!confirm('Generate Grand Total untuk periode ini? Data lama (jika ada) akan ditimpa.')) return false;
        @endif

        return true;
    }

    function tarikAbsensiDirect(btn) {
        if (!btn) return;
        const origText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Memproses...';

        fetch('{{ route('payroll.tarikAbsensi', $payroll->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({})
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Gagal menarik data absensi.');
            }
            // refresh page to show updated counts
            location.reload();
        })
        .catch(e => {
            alert(e.message);
            btn.disabled = false;
            btn.textContent = origText;
        });
    }

    (function(){
        function formatRp(n){
            return 'Rp ' + (n||0).toLocaleString('id-ID');
        }

        function applyGrandFilters(){
            const qEl = document.getElementById('searchGrandTotal');
            const jobEl = document.getElementById('filterJob');
            const q = qEl ? qEl.value.toLowerCase().trim() : '';
            const jobFilter = jobEl ? jobEl.value : '';
            let overall = 0;
            const groupSums = {};

            document.querySelectorAll('.grandtotal-row').forEach(row => {
                const nama = (row.dataset.nama || '').toLowerCase();
                const nip = (row.dataset.nip || '').toLowerCase();
                const section = (row.dataset.section || '');
                const total = parseInt(row.dataset.total) || 0;

                const matchesQuery = !q || nama.includes(q) || nip.includes(q) || row.textContent.toLowerCase().includes(q);
                const matchesJob = !jobFilter || jobFilter === section;
                const visible = matchesQuery && matchesJob;

                row.style.display = visible ? '' : 'none';

                if (visible) {
                    overall += total;
                    groupSums[section] = (groupSums[section] || 0) + total;
                }
            });

            document.querySelectorAll('.job-visible-total').forEach(el => {
                const j = el.dataset.section;
                el.textContent = formatRp(groupSums[j] || 0);
            });

            const visibleEl = document.getElementById('visibleGrandTotal');
            if (visibleEl) visibleEl.textContent = formatRp(overall);
        }

        document.getElementById('searchGrandTotal')?.addEventListener('input', applyGrandFilters);
        document.getElementById('filterJob')?.addEventListener('change', applyGrandFilters);

        // Toggle hide/show per-date columns (explicitly set display to avoid CSS specificity issues)
        const toggleBtn = document.getElementById('toggleDatesBtn');
        const grandGroups = document.getElementById('grandtotal-groups');
        if (toggleBtn && grandGroups) {
            toggleBtn.addEventListener('click', function(){
                const currentlyHidden = grandGroups.classList.toggle('hide-dates');
                const dateCells = grandGroups.querySelectorAll('.date-col');
                dateCells.forEach(td => {
                    td.style.display = currentlyHidden ? 'none' : '';
                });
                toggleBtn.textContent = currentlyHidden ? 'Tampilkan Tanggal' : 'Sembunyikan Tanggal';
            });
        }

        document.querySelectorAll('.grandtotal-section-header').forEach(header => {
            header.addEventListener('click', () => {
                const section = header.closest('.grandtotal-section');
                if (!section) return;
                const body = section.querySelector('.grandtotal-section-body');
                const icon = header.querySelector('.section-toggle-icon');
                if (!body || !icon) return;

                const collapsed = body.classList.toggle('hidden');
                icon.style.transform = collapsed ? 'rotate(-90deg)' : 'rotate(0deg)';
                header.setAttribute('aria-expanded', String(!collapsed));
            });
        });

        const toggleGrandTotalBtn = document.getElementById('toggleGrandTotalBtn');
        const grandTotalBody = document.getElementById('grandTotalBody');
        const grandTotalHiddenKey = 'hideGrandTotalSection';

        function updateGrandTotalToggle(hidden) {
            if (!toggleGrandTotalBtn || !grandTotalBody) return;
            grandTotalBody.classList.toggle('hidden', hidden);
            const icon = hidden
                ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.64 21.64 0 0 1 5.06-6.94"/><path d="M1 1l22 22"/></svg>'
                : '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
            toggleGrandTotalBtn.querySelector('#toggleGrandTotalBtnIcon').innerHTML = icon;
            toggleGrandTotalBtn.querySelector('#toggleGrandTotalBtnText').textContent = hidden ? 'Tampilkan Grand Total' : 'Sembunyikan Grand Total';
            localStorage.setItem(grandTotalHiddenKey, hidden ? '1' : '0');
        }

        if (toggleGrandTotalBtn && grandTotalBody) {
            const persisted = localStorage.getItem(grandTotalHiddenKey) === '1';
            updateGrandTotalToggle(persisted);
            toggleGrandTotalBtn.addEventListener('click', function(){
                updateGrandTotalToggle(grandTotalBody.classList.contains('hidden'));
            });
        }
    })();
</script>
@endsection
