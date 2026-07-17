@extends('layouts.app')

@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800">Detail Absensi</h1>
            <p class="mt-1 text-sm text-slate-500">
                Periode: {{ \Carbon\Carbon::parse($tanggalDari)->format('d M Y') }} — {{ \Carbon\Carbon::parse($tanggalSampai)->format('d M Y') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('absensi.detail.export') }}">
                @csrf
                @foreach(request('selected_users', []) as $pin)
                    <input type="hidden" name="selected_users[]" value="{{ $pin }}">
                @endforeach
                <input type="hidden" name="tanggal_dari" value="{{ request('tanggal_dari', $tanggalDari) }}">
                <input type="hidden" name="tanggal_sampai" value="{{ request('tanggal_sampai', $tanggalSampai) }}">
                @foreach(request('tl_order', []) as $tlId)
                    <input type="hidden" name="tl_order[]" value="{{ $tlId }}">
                @endforeach
                <button type="submit" class="pbtn pbtn-secondary">
                    <span class="pbtn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                    </span>
                    <span>Export Excel</span>
                </button>
            </form>
            <a href="{{ route('absensi.index') }}" class="pbtn pbtn-secondary">
                <span class="pbtn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                </span>
                <span>Kembali</span>
            </a>
        </div>
    </div>

    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            {{-- Search --}}
            <input type="text" id="searchDetail" 
                placeholder="Cari PIN atau Nama..."
                class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-300 w-56">
            {{-- Total rows info --}}
            <span class="text-xs text-slate-400" id="rowInfo"></span>
        </div>
        <div class="flex items-center gap-2">
            {{-- Rows per page --}}
            <select id="rowsPerPage" onchange="renderDetailPage(1)"
                class="border border-slate-200 rounded-lg px-3 py-1.5 text-xs bg-slate-50 focus:outline-none">
                <option value="25">25</option>
                <option value="50" selected>50</option>
                <option value="100">100</option>
            </select>
        </div>
    </div>

    <div class="bg-white rounded-[11px] border border-[#C8D9E6] overflow-hidden shadow-[0_1px_4px_rgba(47,65,86,.06)]">
        <div class="overflow-auto max-h-[70vh]">
            <table class="w-full text-[12.5px] text-[#2F4156]">
                <thead class="sticky top-0 bg-[#EAF1F6] border-b border-[#C8D9E6]">
                    <tr>
                        <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">No</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">NIP</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">Nama</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">L/P</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">Jabatan</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">Tanggal</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">In</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">Out</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">Overtime</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">Keterangan</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">TL</th>
                        <th class="px-4 py-3 text-left text-[11px] font-medium text-[#567C8D] uppercase tracking-wide">Detail</th>
                    </tr>
                </thead>
                <tbody class="border-t border-[#EAF1F6]">
                    @php
                        $namaHari = [
                            'Sunday' => 'Minggu',
                            'Monday' => 'Senin',
                            'Tuesday' => 'Selasa',
                            'Wednesday' => 'Rabu',
                            'Thursday' => 'Kamis',
                            'Friday' => 'Jumat',
                            'Saturday' => 'Sabtu',
                        ];
                        $no = 1;
                    @endphp

                    @foreach($selectedUsers as $pin)
                        @php $karyawan = $nipData[$pin] ?? null; @endphp
                        @foreach($periode as $tgl)
                            @php
                                $dayName = $namaHari[date('l', strtotime($tgl))] ?? '';
                                $tglDisplay = $dayName . ', ' . date('d/m/Y', strtotime($tgl));
                                $isSunday = date('N', strtotime($tgl)) == 7;

                                // Use pre-computed display data
                                $result = $displayData[$pin . '_' . $tgl] ?? [];
                                
                                if ($result['skip'] ?? false) {
                                    continue; // skip this row, OUT already used by previous night's shift
                                }

                                $inTs = $result['in_ts'] ?? null;
                                $outTs = $result['out_ts'] ?? null;

                                $dayLogs = $logs[$pin . '_' . $tgl] ?? collect();

                                $inDisplay = $inTs ? date('H.i', $inTs) : '-';
                                $outDisplay = $outTs ? date('H.i', $outTs) : '-';

                                $overtimeDisplay = '----';
                                if ($outTs) {
                                    $threshold = strtotime($tgl . ' 16:30:00');
                                    $minutes = $outTs > $threshold ? floor(($outTs - $threshold) / 60) : 0;
                                    $overtimeDisplay = $minutes > 0 ? $minutes . ' menit' : '----';
                                }

                                $absenceNote = $absenceNotes[$pin][$tgl] ?? null;
                                $absenceCode = $absenceNote->code ?? null;
                                $absenceText = $absenceNote->note ?? null;
                                $codeLabels = [
                                    'S' => 'S (Sakit)',
                                    'A' => 'A (Alpha)',
                                    'I' => 'I (Izin)',
                                    'SSD' => 'SSD (Sakit Surat Dokter)',
                                    'Cuti' => 'Cuti',
                                    'GL' => 'GL (Ganti Libur)',
                                    'Dll' => 'Dll (Lainnya)',
                                ];

                                if ($isSunday) {
                                    $keterangan = 'Minggu';
                                    $rowClass = 'bg-[#F9FBFD]';
                                } elseif (!$inTs && !$outTs) {
                                    $keterangan = $absenceCode ? ($codeLabels[$absenceCode] ?? $absenceCode) : '-';
                                    if ($absenceText) {
                                        $keterangan .= ' — ' . $absenceText;
                                    }
                                    $rowClass = 'bg-[#FFF3DC]';
                                } else {
                                    $keterangan = '----';
                                    $rowClass = '';
                                }

                                $jabatan = trim(($karyawan->job_title ?? '-') . ' (' . ($karyawan->job_level ?? '-') . ')');
                                $tlName = $tlMap[$karyawan->tl_id ?? null] ?? '-';
                            @endphp

                            <tr class="border-b border-[#EAF1F6] last:border-0 hover:bg-[#F9FBFD] transition-colors detail-row {{ $rowClass }}"
                                data-pin="{{ $pin }}"
                                data-nama="{{ strtolower($karyawan->nama ?? '') }}"
                                data-nip="{{ strtolower($karyawan->nip ?? '') }}"
                                data-search="{{ strtolower(($karyawan->nama ?? '') . ' ' . ($karyawan->nip ?? '') . ' ' . $pin) }}">
                                <td class="px-4 py-3 text-[11px] text-[#8BAFC4]">{{ $no++ }}</td>
                                <td class="px-4 py-3 font-mono text-[11px]">{{ $karyawan->nip ?? '-' }}</td>
                                <td class="px-4 py-3 font-medium">{{ $karyawan->nama ?? '-' }}</td>
                                <td class="px-4 py-3 text-center text-[12px]">{{ $karyawan->jk ?? '-' }}</td>
                                <td class="px-4 py-3 text-[12px]">{{ $jabatan }}</td>
                                <td class="px-4 py-3 text-[12px] text-[#567C8D]">{{ $tglDisplay }}</td>
                                <td class="px-4 py-3">
                                    @if($inDisplay !== '-')
                                        <span class="text-[#1B7A4A] font-medium">{{ $inDisplay }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if($outDisplay !== '-')
                                        <span class="text-[#567C8D] font-medium">{{ $outDisplay }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-[12px] text-[#9A6200]">{{ $overtimeDisplay }}</td>
                                <td class="px-4 py-3 text-[12px]">{{ $keterangan }}</td>
                                <td class="px-4 py-3 text-[12px] text-[#8BAFC4]">{{ $tlName }}</td>
                                <td class="px-4 py-3">
                                    <button type="button" 
                                        onclick="openSummaryModal('{{ $pin }}')"
                                        class="pbtn pbtn-secondary pbtn-sm">
                                        Detail
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
                </div>
                <div class="flex items-center justify-between mt-4">
                    <div class="text-xs text-slate-400" id="detailPaginationInfo"></div>
                    <div class="flex items-center gap-1" id="detailPaginationButtons"></div>
                </div>
            </div>
    </div>

    <div id="summaryModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 p-6 max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-semibold text-slate-800">📋 Ringkasan Absensi Karyawan</h3>
                <button onclick="closeSummaryModal()" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <div id="summaryContent"></div>
        </div>
    </div>

    @php
        $nipJsonData = $nipData->map(fn($k) => [
            'pin' => $k->pin,
            'nip' => $k->nip,
            'nama' => $k->nama,
            'jk' => $k->jk,
            'job_title' => $k->job_title,
            'job_level' => $k->job_level,
            'kategori_gaji' => $k->kategori_gaji,
        ])->toArray();
    @endphp

    <script>
        const summaryData = @json($summary);
        const nipData = @json($nipJsonData);
        const periodeFrom = '{{ \Carbon\Carbon::parse($tanggalDari)->format("d M Y") }}';
        const periodeTo   = '{{ \Carbon\Carbon::parse($tanggalSampai)->format("d M Y") }}';

        const DETAIL_PER_PAGE = 50;
        let detailCurrentPage = 1;
        let detailFilteredRows = [];

        function initDetailTable() {
            detailFilteredRows = Array.from(document.querySelectorAll('.detail-row'));
            renderDetailPage(1);

            document.getElementById('searchDetail').addEventListener('input', function() {
                const q = this.value.toLowerCase().trim();
                const allRows = Array.from(document.querySelectorAll('.detail-row'));

                if (!q) {
                    // Kalau search kosong, tampilkan semua
                    detailFilteredRows = allRows;
                } else {
                    // Cari berdasarkan data-pin — semua baris dengan pin yang sama ikut tampil
                    // Pertama, cari pin mana yang match dengan query
                    const matchedPins = new Set();
                    allRows.forEach(row => {
                        const nama = (row.dataset.nama || '').toLowerCase();
                        const nip  = (row.dataset.nip  || '').toLowerCase();
                        if (nama.includes(q) || nip.includes(q)) {
                            matchedPins.add(row.dataset.pin);
                        }
                    });

                    // Kemudian filter semua baris yang pin-nya ada di matchedPins
                    detailFilteredRows = allRows.filter(row => matchedPins.has(row.dataset.pin));
                }

                renderDetailPage(1);
            });
        }

        function renderDetailPage(page) {
            detailCurrentPage = page;
            const perPage = parseInt(document.getElementById('rowsPerPage').value);
            const start = (page - 1) * perPage;
            const end = start + perPage;

            // Sembunyikan SEMUA baris dulu
            const allRows = Array.from(document.querySelectorAll('.detail-row'));
            allRows.forEach(row => row.style.display = 'none');

            // Tampilkan hanya baris yang masuk range dari filteredRows
            detailFilteredRows.forEach((row, i) => {
                row.style.display = (i >= start && i < end) ? '' : 'none';
            });

            updateDetailPaginationInfo(perPage);
            updateDetailPaginationButtons(perPage);
        }

        function updateDetailPaginationInfo(perPage) {
            const total = detailFilteredRows.length;
            const start = (detailCurrentPage - 1) * perPage + 1;
            const end = Math.min(detailCurrentPage * perPage, total);
            document.getElementById('detailPaginationInfo').textContent =
                'Menampilkan ' + (total === 0 ? 0 : start) + '–' + end + ' dari ' + total + ' baris';
            document.getElementById('rowInfo').textContent = total + ' baris';
        }

        function updateDetailPaginationButtons(perPage) {
            const totalPages = Math.ceil(detailFilteredRows.length / perPage);
            const container = document.getElementById('detailPaginationButtons');
            container.innerHTML = '';
            if (totalPages <= 1) return;

            const prev = document.createElement('button');
            prev.textContent = '←';
            prev.disabled = detailCurrentPage === 1;
            prev.className = 'px-2 py-1 text-xs rounded border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40';
            prev.onclick = () => renderDetailPage(detailCurrentPage - 1);
            container.appendChild(prev);

            for (let p = 1; p <= Math.min(totalPages, 7); p++) {
                const btn = document.createElement('button');
                btn.textContent = p;
                btn.className = p === detailCurrentPage
                    ? 'px-2 py-1 text-xs rounded bg-indigo-600 text-white border border-indigo-600'
                    : 'px-2 py-1 text-xs rounded border border-slate-200 text-slate-500 hover:bg-slate-50';
                btn.onclick = () => renderDetailPage(p);
                container.appendChild(btn);
            }

            const next = document.createElement('button');
            next.textContent = '→';
            next.disabled = detailCurrentPage === totalPages;
            next.className = 'px-2 py-1 text-xs rounded border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-40';
            next.onclick = () => renderDetailPage(detailCurrentPage + 1);
            container.appendChild(next);
        }

        function openSummaryModal(pin) {
            const s = summaryData[pin];
            const k = nipData[pin];
            if (!s || !k) return;

            const content = document.getElementById('summaryContent');
            content.innerHTML = `
                <div class="bg-slate-50 rounded-xl p-4 mb-4">
                    <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">👤 Informasi Karyawan</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between"><span class="text-slate-500">PIN</span><span class="font-medium font-mono">${pin}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">NIP</span><span class="font-medium">${k.nip || '-'}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Nama</span><span class="font-medium">${k.nama || '-'}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Jenis Kelamin</span><span class="font-medium">${k.jk || '-'}</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Jabatan</span><span class="font-medium">${k.job_title || '-'} (${k.job_level || '-'})</span></div>
                        <div class="flex justify-between"><span class="text-slate-500">Kategori Gaji</span><span class="font-medium">${k.kategori_gaji || '-'}</span></div>
                    </div>
                </div>

                <div class="mb-4">
                    <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">📊 Ringkasan Kehadiran</h4>
                    <p class="text-xs text-slate-400 mb-3">Periode: ${periodeFrom} — ${periodeTo}</p>
                    <div class="grid grid-cols-4 gap-2">
                        <div class="bg-green-50 border border-green-100 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-green-600">${s.hadir}</div>
                            <div class="text-xs text-slate-500 mt-1">Total Hadir</div>
                        </div>
                        <div class="bg-amber-50 border border-amber-100 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-amber-500">${s.tidak_hadir}</div>
                            <div class="text-xs text-slate-500 mt-1">Tidak Hadir</div>
                        </div>
                        <div class="bg-red-50 border border-red-100 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-red-500">${s.codes.A}</div>
                            <div class="text-xs text-slate-500 mt-1">Alpha (A)</div>
                        </div>
                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-blue-500">${s.codes.S}</div>
                            <div class="text-xs text-slate-500 mt-1">Sakit (S)</div>
                        </div>
                        <div class="bg-purple-50 border border-purple-100 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-purple-500">${s.codes.I}</div>
                            <div class="text-xs text-slate-500 mt-1">Izin (I)</div>
                        </div>
                        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-indigo-500">${s.codes.SSD}</div>
                            <div class="text-xs text-slate-500 mt-1">SSD</div>
                        </div>
                        <div class="bg-teal-50 border border-teal-100 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-teal-500">${s.codes.Cuti}</div>
                            <div class="text-xs text-slate-500 mt-1">Cuti</div>
                        </div>
                        <div class="bg-orange-50 border border-orange-100 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-orange-500">${s.codes.GL}</div>
                            <div class="text-xs text-slate-500 mt-1">GL</div>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center">
                            <div class="text-2xl font-bold text-slate-500">${s.codes.DLL}</div>
                            <div class="text-xs text-slate-500 mt-1">DLL</div>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('summaryModal').classList.remove('hidden');
        }

        function closeSummaryModal() {
            document.getElementById('summaryModal').classList.add('hidden');
        }

        document.getElementById('summaryModal').addEventListener('click', function(e) {
            if (e.target === this) closeSummaryModal();
        });

        document.addEventListener('DOMContentLoaded', initDetailTable);
    </script>

    <div class="text-right">
        <a href="{{ route('absensi.index') }}" class="pbtn pbtn-secondary">
            ← Kembali ke Filter
        </a>
    </div>
</div>
@endsection
