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
            <a href="{{ route('payroll.index') }}"
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

    {{-- Tabel detail --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-[#F8FAFC] border-b border-[#E5E7EB]">
                <tr>
                    <th class="px-3 py-2.5 text-left font-semibold text-slate-400 uppercase tracking-wide">Nama</th>
                    <th class="px-3 py-2.5 text-left font-semibold text-slate-400 uppercase tracking-wide">NIP</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Hadir</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Alpha</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Izin</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Sakit</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Gaji Pokok</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Lembur (Approval)</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Tambahan</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Potongan</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Total</th>
                    @if($payroll->status === 'draft')
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($details as $d)
                <tr class="border-b border-[#E5E7EB]/50 hover:bg-[#F8FAFC]" id="row-{{ $d->id }}">
                    <td class="px-3 py-2.5 font-medium text-slate-800">{{ $d->nama }}</td>
                    <td class="px-3 py-2.5 text-slate-400 font-mono">{{ $d->nip }}</td>
                    <td class="px-3 py-2.5 text-center text-green-600 font-medium">{{ $d->hadir }}</td>
                    <td class="px-3 py-2.5 text-center text-red-500">{{ $d->alpha }}</td>
                    <td class="px-3 py-2.5 text-center text-amber-500">{{ $d->izin }}</td>
                    <td class="px-3 py-2.5 text-center text-blue-500">{{ $d->sakit }}</td>
                    <td class="px-3 py-2.5 text-right text-slate-700">Rp {{ number_format($d->gaji_pokok, 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right">
                        @if($d->lembur_menit > 0)
                            <div class="flex items-center justify-end gap-2">
                                <span class="{{ $d->lembur_approved ? 'text-amber-600' : 'text-slate-300' }} font-medium">
                                    Rp {{ number_format($d->gaji_lembur, 0, ',', '.') }}
                                </span>
                                @if($payroll->status === 'draft')
                                <button type="button"
                                    onclick="toggleLembur({{ $d->id }}, this)"
                                    data-approved="{{ $d->lembur_approved ? '1' : '0' }}"
                                    data-potensi="{{ $d->potensi_lembur }}"
                                    data-jam="{{ round($d->lembur_menit / 60, 1) }}"
                                    class="text-xs px-2 py-1 rounded-full border transition whitespace-nowrap
                                        {{ $d->lembur_approved 
                                            ? 'border-[#22C55E] text-[#22C55E] bg-[#22C55E]/10' 
                                            : 'border-amber-200 text-amber-500 hover:border-amber-400 hover:bg-amber-50' }}">
                                    @if($d->lembur_approved)
                                        ✅ Approved
                                    @else
                                        Approve? Rp {{ number_format($d->potensi_lembur, 0, ',', '.') }} ({{ round($d->lembur_menit / 60, 1) }} jam)
                                    @endif
                                </button>
                                @endif
                            </div>
                        @else
                            <span class="text-slate-300">-</span>
                        @endif
                    </td>
                    <td class="px-3 py-2.5 text-right text-green-600">Rp {{ number_format($d->tambahan, 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right text-red-500">Rp {{ number_format($d->potongan, 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-[#4F46E5]">Rp {{ number_format($d->total_gaji, 0, ',', '.') }}</td>
                    @if($payroll->status === 'draft')
                    <td class="px-3 py-2.5 text-center">
                        <div class="flex gap-1 justify-center">
                            <button type="button"
                                onclick="openEditModal({{ $d->id }}, {{ $d->tambahan }}, {{ $d->potongan }}, '{{ addslashes($d->keterangan ?? '') }}')"
                                class="text-xs px-2 py-1 rounded-lg border border-[#E5E7EB] text-slate-600 hover:bg-slate-50">
                                Edit
                            </button>
                            <button type="button"
                                onclick="openKoreksiModal({{ $d->id }}, '{{ addslashes($d->nama) }}')"
                                class="text-xs px-2 py-1 rounded-lg border border-indigo-200 text-[#4F46E5] hover:bg-indigo-50">
                                Koreksi
                            </button>
                        </div>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-[#F8FAFC] border-t-2 border-[#E5E7EB]">
                <tr>
                    <td colspan="6" class="px-3 py-2.5 font-semibold text-slate-700 text-right">TOTAL</td>
                    <td class="px-3 py-2.5 text-right font-bold text-slate-800">Rp {{ number_format($details->sum('gaji_pokok'), 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-amber-600">Rp {{ number_format($details->sum('gaji_lembur'), 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-green-600">Rp {{ number_format($details->sum('tambahan'), 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-red-500">Rp {{ number_format($details->sum('potongan'), 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-[#4F46E5]">Rp {{ number_format($details->sum('total_gaji'), 0, ',', '.') }}</td>
                    @if($payroll->status === 'draft')<td></td>@endif
                </tr>
            </tfoot>
        </table>
    </div>
</div>

{{-- Modal Edit Tambahan/Potongan --}}
<div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-slate-800">Edit Tambahan / Potongan</h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <form id="editForm" method="POST">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="text-xs text-slate-500 mb-1 block">Tambahan (Rp)</label>
                <input type="number" name="tambahan" id="edit_tambahan" min="0" value="0"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
            </div>
            <div class="mb-3">
                <label class="text-xs text-slate-500 mb-1 block">Potongan (Rp)</label>
                <input type="number" name="potongan" id="edit_potongan" min="0" value="0"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
            </div>
            <div class="mb-4">
                <label class="text-xs text-slate-500 mb-1 block">Keterangan</label>
                <input type="text" name="keterangan" id="edit_keterangan"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
            </div>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeEditModal()"
                    class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm">Batal</button>
                <button type="submit"
                    class="bg-[#4F46E5] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#4338CA]">Simpan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Koreksi Absensi --}}
<div id="koreksiModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl mx-4 p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-semibold text-slate-800">✏️ Koreksi Absensi</h3>
                <p class="text-xs text-slate-400 mt-0.5" id="koreksiNama"></p>
            </div>
            <button onclick="closeKoreksiModal()" class="text-slate-400 hover:text-slate-600 text-xl">✕</button>
        </div>

        <div id="koreksiLoading" class="text-center py-8 text-slate-400 text-sm">
            Memuat data...
        </div>

        <div id="koreksiContent" class="hidden">
            <div class="overflow-x-auto mb-4">
                <table class="w-full text-xs">
                    <thead class="bg-[#F8FAFC] border-b border-[#E5E7EB]">
                        <tr>
                            <th class="px-3 py-2 text-left text-slate-400 font-semibold uppercase tracking-wide">Tanggal</th>
                            <th class="px-3 py-2 text-center text-slate-400 font-semibold uppercase tracking-wide">FP In</th>
                            <th class="px-3 py-2 text-center text-slate-400 font-semibold uppercase tracking-wide">FP Out</th>
                            <th class="px-3 py-2 text-center text-slate-400 font-semibold uppercase tracking-wide">Koreksi In</th>
                            <th class="px-3 py-2 text-center text-slate-400 font-semibold uppercase tracking-wide">Koreksi Out</th>
                            <th class="px-3 py-2 text-center text-slate-400 font-semibold uppercase tracking-wide">Status</th>
                            <th class="px-3 py-2 text-left text-slate-400 font-semibold uppercase tracking-wide">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="koreksiTableBody"></tbody>
                </table>
            </div>

            <div class="flex gap-3 justify-end">
                <button type="button" onclick="closeKoreksiModal()"
                    class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm">Batal</button>
                <button type="button" onclick="submitKoreksi()"
                    class="bg-[#4F46E5] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#4338CA]">
                    💾 Simpan & Recalculate
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function openEditModal(id, tambahan, potongan, keterangan) {
    document.getElementById('edit_tambahan').value = tambahan;
    document.getElementById('edit_potongan').value = potongan;
    document.getElementById('edit_keterangan').value = keterangan;
    document.getElementById('editForm').action = '/payroll/detail/' + id;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

let currentKoreksiDetailId = null;
let koreksiRows = [];

function openKoreksiModal(detailId, nama) {
    currentKoreksiDetailId = detailId;
    document.getElementById('koreksiNama').textContent = nama;
    document.getElementById('koreksiLoading').classList.remove('hidden');
    document.getElementById('koreksiContent').classList.add('hidden');
    document.getElementById('koreksiModal').classList.remove('hidden');

    fetch(`/payroll/detail/${detailId}/koreksi`, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(data => {
        koreksiRows = data.rows;
        renderKoreksiTable(data.rows);
        document.getElementById('koreksiLoading').classList.add('hidden');
        document.getElementById('koreksiContent').classList.remove('hidden');
    })
    .catch(e => {
        alert('Gagal memuat data: ' + e.message);
        closeKoreksiModal();
    });
}

function renderKoreksiTable(rows) {
    const tbody = document.getElementById('koreksiTableBody');
    tbody.innerHTML = '';

    rows.forEach((row, i) => {
        const isSunday = row.is_sunday;
        const bgClass  = isSunday ? 'bg-slate-50 text-slate-400' : '';

        const statusOptions = ['H','A','I','S','GL','Cuti','DLL'].map(s =>
            `<option value="${s}" ${row.kor_status === s ? 'selected' : ((!row.kor_status && s === 'H') ? 'selected' : '')}>${s}</option>`
        ).join('');

        tbody.innerHTML += `
        <tr class="border-b border-[#E5E7EB] ${bgClass}" id="kor-row-${i}">
            <td class="px-3 py-1.5 font-medium">${row.tgl_display}</td>
            <td class="px-3 py-1.5 text-center text-green-600">${row.fp_in || '-'}</td>
            <td class="px-3 py-1.5 text-center text-blue-600">${row.fp_out || '-'}</td>
            <td class="px-3 py-1.5 text-center">
                ${isSunday ? '-' : `<input type="time" value="${row.kor_in || ''}"
                    class="border border-[#E5E7EB] rounded px-2 py-1 text-xs w-24 focus:outline-none focus:ring-1 focus:ring-[#4F46E5]/30"
                    onchange="updateKoreksiRow(${i}, 'jam_in', this.value)">`}
            </td>
            <td class="px-3 py-1.5 text-center">
                ${isSunday ? '-' : `<input type="time" value="${row.kor_out || ''}"
                    class="border border-[#E5E7EB] rounded px-2 py-1 text-xs w-24 focus:outline-none focus:ring-1 focus:ring-[#4F46E5]/30"
                    onchange="updateKoreksiRow(${i}, 'jam_out', this.value)">`}
            </td>
            <td class="px-3 py-1.5 text-center">
                ${isSunday ? 'Minggu' : `<select class="border border-[#E5E7EB] rounded px-2 py-1 text-xs focus:outline-none"
                    onchange="updateKoreksiRow(${i}, 'status', this.value)">
                    ${statusOptions}
                </select>`}
            </td>
            <td class="px-3 py-1.5">
                ${isSunday ? '-' : `<input type="text" value="${row.kor_ket || ''}" placeholder="opsional"
                    class="border border-[#E5E7EB] rounded px-2 py-1 text-xs w-full focus:outline-none"
                    onchange="updateKoreksiRow(${i}, 'keterangan', this.value)">`}
            </td>
        </tr>`;
    });
}

function updateKoreksiRow(i, field, value) {
    koreksiRows[i][field] = value;
}

function submitKoreksi() {
    const payload = koreksiRows.filter(r => !r.is_sunday).map(r => ({
        tgl        : r.tgl,
        jam_in     : r.jam_in     || null,
        jam_out    : r.jam_out    || null,
        status     : r.status     || 'H',
        keterangan : r.keterangan || null,
    }));

    fetch(`/payroll/detail/${currentKoreksiDetailId}/koreksi`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ rows: payload })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeKoreksiModal();
            location.reload(); // reload untuk update total gaji
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(e => alert('Gagal: ' + e.message));
}

function closeKoreksiModal() {
    document.getElementById('koreksiModal').classList.add('hidden');
    currentKoreksiDetailId = null;
    koreksiRows = [];
}

document.getElementById('koreksiModal').addEventListener('click', function(e) {
    if (e.target === this) closeKoreksiModal();
});

function toggleLembur(detailId, btn) {
    fetch(`/payroll/detail/${detailId}/toggle-lembur`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) return;

        const approved = data.lembur_approved;
        const potensi  = parseInt(btn.dataset.potensi);
        const jam      = btn.dataset.jam;

        btn.dataset.approved = approved ? '1' : '0';
        btn.textContent = approved
            ? '✅ Approved'
            : `Approve? Rp ${potensi.toLocaleString('id-ID')} (${jam} jam)`;
        btn.className = 'text-xs px-2 py-1 rounded-full border transition whitespace-nowrap ' +
            (approved
                ? 'border-[#22C55E] text-[#22C55E] bg-[#22C55E]/10'
                : 'border-amber-200 text-amber-500 hover:border-amber-400 hover:bg-amber-50');

        const lemburSpan = btn.previousElementSibling;
        lemburSpan.className = (approved ? 'text-amber-600' : 'text-slate-300') + ' font-medium';
        lemburSpan.textContent = 'Rp ' + data.gaji_lembur.toLocaleString('id-ID');

        const row = btn.closest('tr');
        const totalCell = row.querySelector('td:nth-last-child(2)');
        if (totalCell) {
            totalCell.textContent = 'Rp ' + data.total_gaji.toLocaleString('id-ID');
        }
    })
    .catch(e => alert('Gagal: ' + e.message));
}
</script>
@endsection
