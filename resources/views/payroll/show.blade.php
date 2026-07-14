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
                    <div class="flex flex-wrap gap-2 pt-3">
                        <a href="{{ route('borongan.review', $cabutImport->id) }}"
                            class="flex-1 text-center border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-xs hover:bg-slate-50 transition">
                            Review
                        </a>
                        <a href="{{ route('borongan.rekapIndex', $cabutImport->id) }}"
                            class="flex-1 text-center border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-xs hover:bg-slate-50 transition">
                            Rekap
                        </a>
                        <a href="{{ route('borongan.create', ['payroll_id' => $payroll->id, 'jenis' => 'cabut', 'revisi' => 1]) }}"
                            class="flex-1 text-center border border-[#4F46E5] text-[#4F46E5] px-3 py-2 rounded-lg text-xs hover:bg-[#4F46E5]/5 transition">
                            Upload Revisi
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
                    <div class="flex flex-wrap gap-2 pt-3">
                        <a href="{{ route('borongan.review', $hcrImport->id) }}"
                            class="flex-1 text-center border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-xs hover:bg-slate-50 transition">
                            Review
                        </a>
                        <a href="{{ route('borongan.rekapIndex', $hcrImport->id) }}"
                            class="flex-1 text-center border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-xs hover:bg-slate-50 transition">
                            Rekap
                        </a>
                        <a href="{{ route('borongan.create', ['payroll_id' => $payroll->id, 'jenis' => 'cetak', 'revisi' => 1]) }}"
                            class="flex-1 text-center border border-[#4F46E5] text-[#4F46E5] px-3 py-2 rounded-lg text-xs hover:bg-[#4F46E5]/5 transition">
                            Upload Revisi
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
                    <div class="flex flex-wrap gap-2 pt-3">
                        <a href="{{ route('borongan.review', $mouldingImport->id) }}"
                            class="flex-1 text-center border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-xs hover:bg-slate-50 transition">
                            Review
                        </a>
                        <a href="{{ route('borongan.rekapIndex', $mouldingImport->id) }}"
                            class="flex-1 text-center border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-xs hover:bg-slate-50 transition">
                            Rekap
                        </a>
                        <a href="{{ route('borongan.create', ['payroll_id' => $payroll->id, 'jenis' => 'moulding', 'revisi' => 1]) }}"
                            class="flex-1 text-center border border-[#4F46E5] text-[#4F46E5] px-3 py-2 rounded-lg text-xs hover:bg-[#4F46E5]/5 transition">
                            Upload Revisi
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
                        class="inline-block bg-[#4F46E5] text-white px-3 py-2 rounded-lg text-xs hover:bg-[#4338CA] transition">
                        Tarik Data
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
                            class="w-full text-center border border-[#4F46E5] text-[#4F46E5] px-3 py-2 rounded-lg text-xs hover:bg-indigo-50 transition font-medium">
                            Tarik Data
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
    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-800">Grand Total</h2>
            <div class="flex items-center gap-3">
                <form id="generateGrandTotalForm" method="POST" action="{{ route('payroll.generateGrandTotal', $payroll->id) }}" onsubmit="return confirmGenerateGrandTotal(event)">
                    @csrf
                    <input type="hidden" name="force" id="forceGrandTotal" value="0">
                    <button type="submit" class="inline-flex items-center gap-2 bg-[#6D28D9] text-white px-4 py-2 rounded-full text-sm hover:bg-[#5B21B6] shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M3 3a1 1 0 011-1h4a1 1 0 110 2H5v12h10V4h-3a1 1 0 110-2h4a1 1 0 011 1v14a1 1 0 01-1 1H4a1 1 0 01-1-1V3z" /></svg>
                        Generate Grand Total
                    </button>
                </form>
            </div>
        </div>

        <p class="text-sm text-slate-500 mb-4">Grand Total akan tersedia setelah semua jenis di-approve</p>

        {{-- Controls: search + section filter + visible total --}}
        @php
            $sectionOptions = isset($grandTotals) ? $grandTotals->pluck('section')->unique()->filter()->values() : collect();
            $sectionLabels = ['cabut' => 'Cabut', 'hcr' => 'Titil Hcr', 'moulding' => 'Moulding', 'harian' => 'Harian'];
        @endphp
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <input id="searchGrandTotal" type="text" placeholder="Cari NIP atau Nama..."
                class="border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none w-64" />

            <select id="filterJob" class="border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none">
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

            <div class="flex items-center justify-end mb-2 gap-2">
                <button id="toggleDatesBtn" type="button" class="border border-[#E5E7EB] text-slate-600 px-3 py-1 rounded-lg text-xs">Sembunyikan Tanggal</button>
            </div>

            <div id="grandtotal-groups" class="space-y-6">
                @foreach($groups as $section => $group)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-semibold text-slate-700">REKAPITULASI BAGIAN {{ strtoupper($sectionLabels[$section] ?? $section) }}</div>
                            <div class="text-sm text-slate-500">Total Section: <span class="job-visible-total font-medium" data-section="{{ $section }}">Rp {{ number_format($group->sum('total_akhir'),0,',','.') }}</span></div>
                        </div>

                        <div class="overflow-x-auto">
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

    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6 mt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-800">Pengajuan & Pencairan</h3>
            <div class="flex gap-3">
                <form method="POST" action="{{ route('payroll.generatePengajuan', $payroll->id) }}" onsubmit="return confirm('Generate Pengajuan dari Grand Total saat ini? Data lama akan ditimpa.')" style="display:inline;">
                    @csrf
                    <button type="submit" class="border border-[#4F46E5] text-[#4F46E5] px-4 py-2 rounded-lg text-sm hover:bg-[#4F46E5]/5">
                        Generate Pengajuan
                    </button>
                </form>
                @if($sudahAdaPengajuan)
                    <a href="{{ route('payroll.exportPengajuan', $payroll->id) }}" class="bg-[#4F46E5] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#4338CA] inline-block">
                        📥 Export Excel
                    </a>
                    <a href="{{ route('payroll.pengajuan', $payroll->id) }}" class="border border-[#4F46E5] text-[#4F46E5] px-4 py-2 rounded-lg text-sm hover:bg-[#4F46E5]/5 inline-block">
                        📄 Lihat Pengajuan
                    </a>
                @else
                    <span title="Generate Pengajuan dulu" class="inline-flex items-center justify-center bg-slate-200 text-slate-500 px-4 py-2 rounded-lg text-sm cursor-not-allowed">
                        📥 Export Excel
                    </span>
                    <span title="Generate Pengajuan dulu" class="inline-flex items-center justify-center bg-slate-200 text-slate-500 px-4 py-2 rounded-lg text-sm cursor-not-allowed">
                        📄 Lihat Pengajuan
                    </span>
                @endif
            </div>
        </div>
        <p class="text-xs text-slate-400">Generate Pengajuan akan mengambil data dari Grand Total yang sudah di-generate, digabung dengan data rekening dari Data Bank.</p>
    </div>
</div>
@endsection

<script>
    function confirmGenerateGrandTotal(e) {
        e.preventDefault();
        const form = document.getElementById('generateGrandTotalForm');

        @if(!$bisaGenerateGrandTotal)
            const lanjut = confirm('⚠️ Belum semua jenis di-approve / periode belum final.\n\nGenerate Grand Total sekarang tetap bisa dilakukan, tapi datanya mungkin belum lengkap. Lanjutkan?');
            if (!lanjut) return false;
            document.getElementById('forceGrandTotal').value = '1';
        @else
            if (!confirm('Generate Grand Total untuk periode ini? Data lama (jika ada) akan ditimpa.')) return false;
        @endif

        form.submit();
        return false;
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
    })();
</script>
