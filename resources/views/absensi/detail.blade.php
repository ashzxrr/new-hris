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
                    Export CSV
                </button>
            </form>
            <a href="{{ route('absensi.index') }}" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-slate-700">
                Kembali
            </a>
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

                            <tr class="border-t border-slate-100 text-sm {{ $rowClass }}">
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
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-right">
        <a href="{{ route('absensi.index') }}" class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-700">
            ← Kembali ke Filter
        </a>
    </div>
</div>
@endsection
