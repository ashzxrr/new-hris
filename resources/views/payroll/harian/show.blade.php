@extends('layouts.app')
@section('content')
<div>
    <div class="flex items-center justify-between mb-5">
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
        <div class="flex gap-3">
            <a href="{{ isset($payroll) ? route('payroll.show', $payroll->id) : route('payroll.index') }}"
                class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm">← Kembali</a>
            <a href="{{ route('payroll.export.slip', $payroll->id) }}"
                class="border border-[#22C55E] text-[#22C55E] px-4 py-2 rounded-lg text-sm hover:bg-green-50 transition">
                📄 Export Slip Gaji
            </a>
            @if($payroll->status === 'draft')
            <form method="POST" action="{{ route('payroll.finalize', $payroll->id) }}">
                @csrf @method('PUT')
                <button type="submit"
                    class="bg-[#22C55E] text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600"
                    onclick="return confirm('Finalisasi payroll? Status tidak bisa diubah kembali.')">
                    ✅ Finalisasi
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Karyawan</div>
            <div class="text-2xl font-bold text-slate-800">{{ $details->count() }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Gaji Pokok</div>
            <div class="text-xl font-bold text-slate-800">Rp {{ number_format($details->sum('gaji_pokok'), 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Lembur</div>
            <div class="text-xl font-bold text-amber-500">Rp {{ number_format($details->sum('gaji_lembur'), 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Keseluruhan</div>
            <div class="text-xl font-bold text-[#4F46E5]">Rp {{ number_format($details->sum('total_gaji'), 0, ',', '.') }}</div>
        </div>
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <input type="text" id="searchHarian" placeholder="Cari NIP atau Nama..."
            class="w-full border border-[#E5E7EB] rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
    </div>

    {{-- Tabel detail --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-[#F8FAFC] border-b border-[#E5E7EB]">
                <tr>
                    <th class="px-3 py-2.5 text-left font-semibold text-slate-400 uppercase tracking-wide">NIP</th>
                    <th class="px-3 py-2.5 text-left font-semibold text-slate-400 uppercase tracking-wide">Nama</th>
                    <th class="px-3 py-2.5 text-left font-semibold text-slate-400 uppercase tracking-wide">Bagian</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Hadir</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Alpha</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Izin</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Sakit</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">ST</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Gaji Pokok</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Lembur</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Tambahan</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Potongan</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Total</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($details as $d)
                <tr class="border-b border-[#E5E7EB]/50 hover:bg-[#F8FAFC]" id="row-{{ $d->id }}">
                    <td class="px-3 py-2.5 text-slate-400 font-mono">{{ $d->nip }}</td>
                    <td class="px-3 py-2.5 font-medium text-slate-800">{{ $d->nama }}</td>
                    <td class="px-3 py-2.5 text-slate-500">{{ $d->bagian ?? '-' }}</td>
                    <td class="px-3 py-2.5 text-center text-green-600 font-medium">{{ $d->hadir }}</td>
                    <td class="px-3 py-2.5 text-center text-red-500">{{ $d->alpha }}</td>
                    <td class="px-3 py-2.5 text-center text-amber-500">{{ $d->izin }}</td>
                    <td class="px-3 py-2.5 text-center text-blue-500">{{ $d->sakit }}</td>
                    <td class="px-3 py-2.5 text-center text-slate-700">{{ $d->setengah_hari }}</td>
                    <td class="px-3 py-2.5 text-right text-slate-700">Rp {{ number_format($d->gaji_pokok, 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right">
                        @if($d->gaji_lembur > 0)
                            <span class="text-amber-600 font-medium">Rp {{ number_format($d->gaji_lembur, 0, ',', '.') }}</span>
                        @else
                            <span class="text-slate-300">-</span>
                        @endif
                    </td>
                    <td class="px-3 py-2.5 text-right text-green-600">Rp {{ number_format($d->tambahan, 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right text-red-500">
                        @php
                            $stDeduction = $d->setengah_hari * ($d->nominal_harian / 2);
                            $displayPotongan = $d->potongan + $stDeduction;
                        @endphp
                        Rp {{ number_format($displayPotongan, 0, ',', '.') }}
                    </td>
                    <td class="px-3 py-2.5 text-right font-bold text-[#4F46E5]" id="total-{{ $d->id }}">Rp {{ number_format($d->total_gaji, 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-center">
                        <button type="button"
                            onclick="openDetailModal({{ $d->id }}, '{{ addslashes($d->nama) }}', '{{ $d->nip }}', {{ $d->tambahan }}, {{ $d->potongan }}, '{{ addslashes($d->keterangan ?? '') }}')"
                            class="text-xs px-3 py-1.5 rounded-lg bg-[#4F46E5] text-white hover:bg-[#4338CA] transition font-medium">
                            Detail
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-[#F8FAFC] border-t-2 border-[#E5E7EB]">
                <tr>
                    <td colspan="7" class="px-3 py-2.5 font-semibold text-slate-700 text-right">TOTAL</td>
                    <td class="px-3 py-2.5 text-right font-bold text-slate-800">Rp {{ number_format($details->sum('gaji_pokok'), 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-amber-600">Rp {{ number_format($details->sum('gaji_lembur'), 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-green-600">Rp {{ number_format($details->sum('tambahan'), 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-red-500">Rp {{ number_format($details->sum(fn($d) => $d->potongan + ($d->setengah_hari * ($d->nominal_harian / 2))), 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-[#4F46E5]">Rp {{ number_format($details->sum('total_gaji'), 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- ===== MODAL DETAIL KARYAWAN ===== --}}
<div id="detailModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[92vh] flex flex-col">

        {{-- Header --}}
        <div class="flex items-start justify-between px-6 py-4 border-b border-[#E5E7EB] shrink-0">
            <div>
                <h3 class="font-bold text-slate-800 text-base" id="detailNama"></h3>
                <p class="text-xs text-slate-400 mt-0.5" id="detailNip"></p>
            </div>
            <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 text-xl leading-none mt-0.5">✕</button>
        </div>

        {{-- Body scrollable --}}
        <div class="overflow-y-auto flex-1 px-6 py-4">

            {{-- Loading --}}
            <div id="detailLoading" class="text-center py-12 text-slate-400 text-sm">
                <div class="animate-spin text-3xl mb-3">⏳</div>
                Memuat data absensi...
            </div>

            {{-- Content --}}
            <div id="detailContent" class="hidden">

                {{-- Section: Rincian Absensi Harian --}}
                <div class="mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xs font-bold text-[#4F46E5] uppercase tracking-widest">📋 Rincian Per Hari</span>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-[#E5E7EB]">
                        <table class="w-full text-xs">
                            <thead class="bg-[#F8FAFC]">
                                <tr>
                                    <th class="px-3 py-2 text-left text-slate-400 font-semibold uppercase tracking-wide border-b border-[#E5E7EB]">Tanggal</th>
                                    <th class="px-3 py-2 text-center text-slate-400 font-semibold uppercase tracking-wide border-b border-[#E5E7EB]">FP In</th>
                                    <th class="px-3 py-2 text-center text-slate-400 font-semibold uppercase tracking-wide border-b border-[#E5E7EB]">FP Out</th>
                                    <th class="px-3 py-2 text-center text-slate-400 font-semibold uppercase tracking-wide border-b border-[#E5E7EB]">Kor. In</th>
                                    <th class="px-3 py-2 text-center text-slate-400 font-semibold uppercase tracking-wide border-b border-[#E5E7EB]">Kor. Out</th>
                                    <th class="px-3 py-2 text-center text-slate-400 font-semibold uppercase tracking-wide border-b border-[#E5E7EB]">Status</th>
                                    <th class="px-3 py-2 text-center text-slate-400 font-semibold uppercase tracking-wide border-b border-[#E5E7EB]">Lembur</th>
                                    <th class="px-3 py-2 text-center text-slate-400 font-semibold uppercase tracking-wide border-b border-[#E5E7EB]">ACC Lembur</th>
                                    <th class="px-3 py-2 text-left text-slate-400 font-semibold uppercase tracking-wide border-b border-[#E5E7EB]">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody id="detailTableBody"></tbody>
                        </table>
                    </div>
                </div>

                {{-- Section: Tambahan & Potongan --}}
                <div class="mb-2">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="text-xs font-bold text-amber-500 uppercase tracking-widest">⚙️ Potongan & Tambahan</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-slate-500 mb-1 block">Tambahan (Rp)</label>
                            <input type="number" id="detail_tambahan" min="0" value="0"
                                class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                        </div>
                        <div>
                            <label class="text-xs text-slate-500 mb-1 block">Potongan (Rp)</label>
                            <input type="number" id="detail_potongan" min="0" value="0"
                                class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                        </div>
                        <div class="col-span-2">
                            <label class="text-xs text-slate-500 mb-1 block">Keterangan Umum</label>
                            <input type="text" id="detail_keterangan" placeholder="opsional"
                                class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                        </div>
                    </div>
                </div>

            </div>{{-- end #detailContent --}}
        </div>

        {{-- Footer --}}
        <div id="detailFooter" class="hidden shrink-0 px-6 py-4 border-t border-[#E5E7EB] flex items-center justify-between">
            <div class="text-sm text-slate-600">
                Total Akhir: <span id="detailTotalLabel" class="font-bold text-[#4F46E5] text-base ml-1">Rp 0</span>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeDetailModal()"
                    class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm hover:bg-slate-50">Tutup</button>
                @if($payroll->status === 'draft')
                <button type="button" onclick="submitDetail()"
                    class="bg-[#4F46E5] text-white px-5 py-2 rounded-lg text-sm hover:bg-[#4338CA] font-medium flex items-center gap-2">
                    💾 Simpan
                </button>
                @endif
            </div>
        </div>

    </div>
</div>

<script>
const payrollIsDraft = {{ $payroll->status === 'draft' ? 'true' : 'false' }};

let currentDetailId  = null;
let currentDetailNominal = 0;
let detailRows = [];

function openDetailModal(detailId, nama, nip, tambahan, potongan, keterangan) {
    currentDetailId = detailId;

    document.getElementById('detailNama').textContent = nama;
    document.getElementById('detailNip').textContent  = nip;
    document.getElementById('detail_tambahan').value  = tambahan;
    document.getElementById('detail_potongan').value  = potongan;
    document.getElementById('detail_keterangan').value = keterangan;

    document.getElementById('detailLoading').classList.remove('hidden');
    document.getElementById('detailContent').classList.add('hidden');
    document.getElementById('detailFooter').classList.add('hidden');
    document.getElementById('detailModal').classList.remove('hidden');

    fetch(`{{ url('/payroll/detail') }}/${detailId}/koreksi`, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(data => {
        detailRows = data.rows;
        renderDetailTable(data.rows);
        document.getElementById('detailLoading').classList.add('hidden');
        document.getElementById('detailContent').classList.remove('hidden');
        document.getElementById('detailFooter').classList.remove('hidden');
        updateTotalLabel();
    })
    .catch(e => {
        alert('Gagal memuat data: ' + e.message);
        closeDetailModal();
    });
}

function renderDetailTable(rows) {
    const tbody  = document.getElementById('detailTableBody');
    tbody.innerHTML = '';

    const statusOptions = ['H','A','I','S','ST','GL','Cuti','DLL'];

    rows.forEach((row, i) => {
        const isSunday = row.is_sunday;
        const isKoreksi = row.has_kor;

        // Row color cues
        let trClass = 'border-b border-[#E5E7EB]';
        if (isSunday) trClass += ' bg-slate-50 text-slate-400';
        else if (isKoreksi) trClass += ' bg-indigo-50/40';

        // Status indicator dot
        const statusDotMap = { H: 'bg-green-400', A: 'bg-red-400', I: 'bg-amber-400', S: 'bg-blue-400', ST: 'bg-purple-400', GL: 'bg-orange-400', Cuti: 'bg-teal-400', DLL: 'bg-slate-400' };
        const effectiveStatus = row.kor_status || 'H';
        const dot = statusDotMap[effectiveStatus] || 'bg-slate-300';

        const selectOpts = statusOptions.map(s =>
            `<option value="${s}" ${row.kor_status === s ? 'selected' : (!row.kor_status && s === 'H' ? 'selected' : '')}>${s}</option>`
        ).join('');

        const lemburChecked = row.lembur_approved ? 'checked' : '';

        let cells = '';
        if (isSunday) {
            cells = `
                <td class="px-3 py-2 font-medium">${row.tgl_display}</td>
                <td class="px-3 py-2 text-center">-</td>
                <td class="px-3 py-2 text-center">-</td>
                <td class="px-3 py-2 text-center">-</td>
                <td class="px-3 py-2 text-center">-</td>
                <td class="px-3 py-2 text-center text-xs">Minggu</td>
                <td class="px-3 py-2 text-center">-</td>
                <td class="px-3 py-2 text-center">-</td>
                <td class="px-3 py-2">-</td>`;
        } else {
            cells = `
                <td class="px-3 py-2 font-medium text-slate-700">${row.tgl_display}</td>
                <td class="px-3 py-2 text-center text-green-600 font-mono">${row.fp_in || '<span class="text-slate-300">-</span>'}</td>
                <td class="px-3 py-2 text-center text-blue-600 font-mono">${row.fp_out || '<span class="text-slate-300">-</span>'}</td>
                <td class="px-3 py-2 text-center">
                    ${payrollIsDraft
                        ? `<input type="time" value="${row.kor_in || ''}" class="border border-[#E5E7EB] rounded px-1.5 py-1 text-xs w-22 focus:outline-none focus:ring-1 focus:ring-[#4F46E5]/30" onchange="updateDetailRow(${i}, 'jam_in', this.value)">`
                        : (row.kor_in || '<span class="text-slate-300">-</span>')}
                </td>
                <td class="px-3 py-2 text-center">
                    ${payrollIsDraft
                        ? `<input type="time" value="${row.kor_out || ''}" class="border border-[#E5E7EB] rounded px-1.5 py-1 text-xs w-22 focus:outline-none focus:ring-1 focus:ring-[#4F46E5]/30" onchange="updateDetailRow(${i}, 'jam_out', this.value)">`
                        : (row.kor_out || '<span class="text-slate-300">-</span>')}
                </td>
                <td class="px-3 py-2 text-center">
                    ${payrollIsDraft
                        ? `<select class="border border-[#E5E7EB] rounded px-1.5 py-1 text-xs focus:outline-none" onchange="updateDetailRow(${i}, 'status', this.value)">${selectOpts}</select>`
                        : `<span class="inline-flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full ${dot} inline-block"></span>${effectiveStatus}</span>`}
                </td>
                <td class="px-3 py-2 text-center text-slate-500">
                    ${isSunday ? '-' : (payrollIsDraft
                        ? `<div class="flex items-center justify-center gap-2">
                                <input type="number" min="0" class="w-20 px-2 py-1 border border-[#E5E7EB] rounded text-sm" value="${row.lembur_menit || 0}" onchange="updateDetailRow(${i}, 'lembur_menit', parseInt(this.value) || 0)" ${row.is_sunday ? 'disabled' : ''}>
                                <span class="text-[10px] text-slate-400">(${row.lembur_jam || 0} jam)</span>
                            </div>`
                        : (row.lembur_menit > 0 ? `${row.lembur_menit} mnt` : '-'))}
                </td>
                <td class="px-3 py-2 text-center">
                    ${payrollIsDraft
                        ? `<input type="checkbox" ${lemburChecked} class="w-4 h-4 accent-[#4F46E5] cursor-pointer" onchange="updateDetailRow(${i}, 'lembur_approved', this.checked)" ${row.lembur_menit > 0 ? '' : 'disabled'}>`
                        : (row.lembur_approved ? '✅' : '<span class="text-slate-300">-</span>')}
                </td>
                <td class="px-3 py-2">
                    ${payrollIsDraft
                        ? `<input type="text" value="${row.kor_ket || ''}" placeholder="keterangan..." class="border border-[#E5E7EB] rounded px-2 py-1 text-xs w-full focus:outline-none" onchange="updateDetailRow(${i}, 'keterangan', this.value)">`
                        : (row.kor_ket || '<span class="text-slate-300">-</span>')}
                </td>`;
        }

        tbody.innerHTML += `<tr class="${trClass}">${cells}</tr>`;
    });
}

function updateDetailRow(i, field, value) {
    detailRows[i][field] = value;
}

function updateTotalLabel() {
    // Just display static from loaded – will refresh on save
    const totalEl = document.getElementById('detailTotalLabel');
    // Can't calculate easily without nominal on frontend, just leave as refreshed after save
}

function submitDetail() {
    const tambahan   = parseInt(document.getElementById('detail_tambahan').value) || 0;
    const potongan   = parseInt(document.getElementById('detail_potongan').value) || 0;
    const keterangan = document.getElementById('detail_keterangan').value;

    const koreksiPayload = detailRows.filter(r => !r.is_sunday).map(r => ({
        tgl            : r.tgl,
        jam_in         : r.jam_in      || null,
        jam_out        : r.jam_out     || null,
        status         : r.status      || (r.kor_status || 'H'),
        keterangan     : r.keterangan  || null,
        lembur_menit   : r.lembur_menit || 0,
        lembur_approved: r.lembur_approved || false,
    }));

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // 1. Save koreksi absensi + recalculate
    fetch(`{{ url('/payroll/detail') }}/${currentDetailId}/koreksi`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ rows: koreksiPayload })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) throw new Error(data.message);

        // 2. Save tambahan/potongan
        const form = new FormData();
        form.append('_method', 'PUT');
        form.append('_token', csrfToken);
        form.append('tambahan', tambahan);
        form.append('potongan', potongan);
        form.append('keterangan', keterangan);

        return fetch(`{{ url('/payroll/detail') }}/${currentDetailId}`, { method: 'POST', body: form });
    })
    .then(() => {
        closeDetailModal();
        location.reload();
    })
    .catch(e => alert('Gagal menyimpan: ' + e.message));
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
    currentDetailId = null;
    detailRows = [];
}

document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) closeDetailModal();
});
document.getElementById('searchHarian')?.addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    document.querySelectorAll('tbody tr[id^="row-"]').forEach(row => {
        const rowText = row.textContent.toLowerCase();
        row.style.display = !query || rowText.includes(query) ? '' : 'none';
    });
});
</script>
@endsection
