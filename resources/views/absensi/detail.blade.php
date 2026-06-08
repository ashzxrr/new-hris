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
                <button type="submit" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50">
                    📊 Export Excel
                </button>
            </form>
            <a href="{{ route('absensi.index') }}" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-slate-700">
                Kembali
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

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-auto max-h-[70vh]">
            <table class="min-w-full border-separate border-spacing-0 text-left text-sm">
                <thead class="sticky top-0 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-3">NIP</th>
                        <th class="px-3 py-3">Nama</th>
                        <th class="px-3 py-3">L/P</th>
                        <th class="px-3 py-3">Jabatan</th>
                        <th class="px-3 py-3">Tanggal</th>
                        <th class="px-3 py-3">In</th>
                        <th class="px-3 py-3">Out</th>
                        <th class="px-3 py-3">Overtime</th>
                        <th class="px-3 py-3">Keterangan</th>
                        <th class="px-3 py-3">TL</th>
                        <th class="px-3 py-3">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
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

                                $dayLogs = $logs[$pin . '_' . $tgl] ?? collect();
                                $inTimes = $dayLogs->where('status', 'IN')->pluck('datetime')->map(fn($d) => strtotime($d));
                                $outTimes = $dayLogs->where('status', 'OUT')->pluck('datetime')->map(fn($d) => strtotime($d));

                                $inTs = $inTimes->isNotEmpty() ? $inTimes->min() : null;
                                $outTs = $outTimes->isNotEmpty() ? $outTimes->max() : null;

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
                                    $rowClass = 'bg-slate-50';
                                } elseif ($dayLogs->isEmpty()) {
                                    $keterangan = $absenceCode ? ($codeLabels[$absenceCode] ?? $absenceCode) : '-';
                                    if ($absenceText) {
                                        $keterangan .= ' — ' . $absenceText;
                                    }
                                    $rowClass = 'bg-amber-50';
                                } else {
                                    $keterangan = '----';
                                    $rowClass = '';
                                }

                                $jabatan = trim(($karyawan->job_title ?? '-') . ' (' . ($karyawan->job_level ?? '-') . ')');
                                $tlName = $tlMap[$karyawan->tl_id ?? null] ?? '-';
                            @endphp

                            <tr class="border-t border-slate-100 text-sm detail-row {{ $rowClass }}" data-search="{{ strtolower($nipData[$pin]->nama ?? '') }} {{ $pin }}">
                                <td class="px-3 py-2 text-slate-400">{{ $no++ }}</td>
                                <td class="px-3 py-2 font-mono text-xs">{{ $karyawan->nip ?? '-' }}</td>
                                <td class="px-3 py-2 font-medium text-slate-800">{{ $karyawan->nama ?? '-' }}</td>
                                <td class="px-3 py-2 text-center">{{ $karyawan->jk ?? '-' }}</td>
                                <td class="px-3 py-2 text-xs">{{ $jabatan }}</td>
                                <td class="px-3 py-2 text-xs text-slate-600">{{ $tglDisplay }}</td>
                                <td class="px-3 py-2">
                                    @if($inDisplay !== '-')
                                        <span class="text-green-600 font-medium">{{ $inDisplay }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if($outDisplay !== '-')
                                        <span class="text-blue-600 font-medium">{{ $outDisplay }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-xs text-orange-500">{{ $overtimeDisplay }}</td>
                                <td class="px-3 py-2 text-xs">{{ $keterangan }}</td>
                                <td class="px-3 py-2 text-xs text-slate-500">{{ $tlName }}</td>
                                <td class="px-3 py-2">
                                    <button type="button" 
                                        onclick="openSummaryModal('{{ $pin }}')"
                                        class="text-xs px-2 py-1 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition border border-indigo-200">
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

            const searchInput = document.getElementById('searchDetail');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const q = this.value.toLowerCase().trim();
                    const allRows = Array.from(document.querySelectorAll('.detail-row'));
                    detailFilteredRows = allRows.filter(row => {
                        return !q || (row.dataset.search || '').includes(q);
                    });
                    renderDetailPage(1);
                });
            }
        }

        function renderDetailPage(page) {
            detailCurrentPage = page;
            const perPage = parseInt(document.getElementById('rowsPerPage').value);
            const start = (page - 1) * perPage;
            const end = start + perPage;

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
        <a href="{{ route('absensi.index') }}" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700">
            ← Kembali ke Filter
        </a>
    </div>
</div>
@endsection
