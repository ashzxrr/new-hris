@extends('layouts.app')
@section('content')
<div>
    <div class="flex items-center justify-between mb-5">
        <div>
            @php
            $jenisLabels = [
                'cetak' => 'HCR',
                'moulding' => 'Moulding/Cetak',
                'cabut' => 'Cabut',
            ];
        @endphp
        <h1 class="text-xl font-semibold text-slate-800">Rekap Borongan — {{ $jenisLabels[$import->jenis] ?? ucfirst($import->jenis) }}</h1>
            <p class="text-xs text-slate-400 mt-1">
                Periode: {{ \Carbon\Carbon::parse($import->tanggal_dari)->format('d M') }} —
                {{ \Carbon\Carbon::parse($import->tanggal_sampai)->format('d M Y') }}
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('borongan.review', $import->id) }}"
                class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm">← Review</a>
            <a href="{{ $payrollId ? route('payroll.show', $payrollId) : route('borongan.index') }}"
                class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm">List Import</a>
        </div>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Karyawan</div>
            <div class="text-2xl font-bold text-slate-800">{{ $rekaps->count() }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Gram</div>
            <div class="text-xl font-bold text-slate-800">{{ number_format($rekaps->sum('total_gram')) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Upah</div>
            <div class="text-xl font-bold text-[#4F46E5]">Rp {{ number_format($rekaps->sum('total_upah'), 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Akhir</div>
            <div class="text-xl font-bold text-[#22C55E]">Rp {{ number_format($rekaps->sum('total_akhir'), 0, ',', '.') }}</div>
        </div>
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <input type="text" id="searchRekap" placeholder="Cari NIP atau Nama..."
            class="w-full border border-[#E5E7EB] rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
    </div>

    {{-- Tabel rekap --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-[#F8FAFC] border-b border-[#E5E7EB]">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">NIP</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Nama</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Gram</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Upah</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Potongan BPJS</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Potongan Lain</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Tambahan</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Akhir</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400 uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody id="rekapBody">
                @foreach($rekaps as $r)
                <tr class="rekap-row border-b border-[#E5E7EB]/50 hover:bg-[#F8FAFC]"
                    data-nip="{{ strtolower($r->nip) }}"
                    data-nama="{{ strtolower($r->nama) }}"
                    id="rekap-row-{{ $r->rekap_id }}">
                    <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $r->nip }}</td>
                    <td class="px-4 py-3 font-medium text-slate-800">
                        <button type="button"
                            onclick="openDetailModal('{{ $import->id }}', '{{ $r->nip }}', '{{ addslashes($r->nama) }}', {{ $r->rekap_id }})"
                            class="text-[#4F46E5] hover:underline text-left">
                            {{ $r->nama }}
                        </button>
                    </td>
                    <td class="px-4 py-3 text-right text-slate-600">{{ number_format($r->total_gram) }}</td>
                    <td class="px-4 py-3 text-right text-slate-700">Rp {{ number_format($r->total_upah, 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-right text-red-500" id="bpjs-{{ $r->rekap_id }}">
                        {{ $r->potongan_bpjs > 0 ? 'Rp ' . number_format($r->potongan_bpjs, 0, ',', '.') : '-' }}
                    </td>
                    <td class="px-4 py-3 text-right text-red-500" id="pot-{{ $r->rekap_id }}">
                        {{ $r->potongan_lain > 0 ? 'Rp ' . number_format($r->potongan_lain, 0, ',', '.') : '-' }}
                    </td>
                    <td class="px-4 py-3 text-right text-green-600" id="tmb-{{ $r->rekap_id }}">
                        {{ $r->tambahan > 0 ? 'Rp ' . number_format($r->tambahan, 0, ',', '.') : '-' }}
                    </td>
                    <td class="px-4 py-3 text-right font-bold text-[#4F46E5]" id="total-{{ $r->rekap_id }}">
                        Rp {{ number_format($r->total_akhir, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button type="button"
                            onclick="openDetailModal('{{ $import->id }}', '{{ $r->nip }}', '{{ addslashes($r->nama) }}', {{ $r->rekap_id }})"
                            class="text-xs px-2 py-1 rounded-lg border border-[#4F46E5]/30 text-[#4F46E5] hover:bg-[#4F46E5]/5">
                            Detail
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Detail --}}
<div id="detailModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white rounded-t-2xl border-b border-[#E5E7EB] px-6 py-4 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-slate-800" id="modalNama">Detail Karyawan</h3>
                <p class="text-xs text-slate-400" id="modalNip"></p>
            </div>
            <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 text-xl">✕</button>
        </div>

        <div class="p-6">
            {{-- Loading --}}
            <div id="modalLoading" class="text-center py-8 text-slate-400 text-sm">Memuat data...</div>

            {{-- Content --}}
            <div id="modalContent" class="hidden">
                {{-- Tabel harian --}}
                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">📅 Rincian Per Hari</h4>
                <div class="overflow-x-auto mb-6">
                    <table class="w-full text-xs">
                        <thead class="bg-[#F8FAFC] border-b border-[#E5E7EB]">
                            <tr>
                                <th class="px-3 py-2 text-left text-slate-400">Tanggal</th>
                                <th class="px-3 py-2 text-center text-slate-400">IN</th>
                                <th class="px-3 py-2 text-center text-slate-400">OUT</th>
                                <th class="px-3 py-2 text-center text-slate-400">Ket</th>
                                <th class="px-3 py-2 text-right text-slate-400">Gram</th>
                                <th class="px-3 py-2 text-right text-slate-400">Upah</th>
                            </tr>
                        </thead>
                        <tbody id="modalHarianBody"></tbody>
                    </table>
                </div>

                {{-- Form potongan/tambahan --}}
                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">💰 Potongan & Tambahan</h4>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">Potongan BPJS (Rp)</label>
                        <input type="number" id="inputBpjs" min="0" value="0"
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">Potongan Lain (Rp)</label>
                        <input type="number" id="inputPotLain" min="0" value="0"
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">Tambahan (Rp)</label>
                        <input type="number" id="inputTambahan" min="0" value="0"
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                    </div>
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">Keterangan</label>
                        <input type="text" id="inputKeterangan" placeholder="opsional"
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-[#E5E7EB]">
                    <div>
                        <span class="text-xs text-slate-400">Total Akhir:</span>
                        <span class="text-lg font-bold text-[#4F46E5] ml-2" id="modalTotalAkhir">-</span>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="closeDetailModal()"
                            class="pbtn pbtn-secondary">Tutup</button>
                        <button type="button" onclick="saveRekap()"
                            class="pbtn pbtn-primary">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
                            </span>
                            <span>Simpan</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentRekapId = null;
let currentTotalUpah = 0;
const boronganBaseUrl = "{{ url('borongan') }}";

function openDetailModal(importId, nip, nama, rekapId) {
    currentRekapId = rekapId;
    document.getElementById('modalNama').textContent = nama;
    document.getElementById('modalNip').textContent = nip;
    document.getElementById('modalLoading').classList.remove('hidden');
    document.getElementById('modalContent').classList.add('hidden');
    document.getElementById('detailModal').classList.remove('hidden');

    fetch(`${boronganBaseUrl}/${importId}/detail/${encodeURIComponent(nip)}`, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(r => r.json())
    .then(data => {
        const rekap = data.rekap;
        currentTotalUpah = rekap ? rekap.total_upah : 0;

        // Fill form
        document.getElementById('inputBpjs').value     = rekap?.potongan_bpjs  ?? 0;
        document.getElementById('inputPotLain').value  = rekap?.potongan_lain  ?? 0;
        document.getElementById('inputTambahan').value = rekap?.tambahan        ?? 0;
        document.getElementById('inputKeterangan').value = rekap?.keterangan   ?? '';

        updateTotalPreview();

        // Render tabel harian
        const tbody = document.getElementById('modalHarianBody');
        tbody.innerHTML = '';
        data.harian.forEach(h => {
            const isSunday = h.is_sunday;
            const bgClass  = isSunday ? 'bg-slate-50 text-slate-400' :
                             (!h.hadir && !isSunday && h.gram == 0) ? 'bg-amber-50' : '';
            tbody.innerHTML += `
            <tr class="border-b border-[#E5E7EB] ${bgClass}">
                <td class="px-3 py-1.5">${formatTgl(h.tanggal)}</td>
                <td class="px-3 py-1.5 text-center ${h.jam_in ? 'text-green-600' : 'text-slate-300'}">${h.jam_in || '-'}</td>
                <td class="px-3 py-1.5 text-center ${h.jam_out ? 'text-blue-600' : 'text-slate-300'}">${h.jam_out || '-'}</td>
                <td class="px-3 py-1.5 text-center">
                    ${h.keterangan ? `<span class="text-xs bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded">${h.keterangan}</span>` : '-'}
                </td>
                <td class="px-3 py-1.5 text-right ${h.gram > 0 ? 'text-slate-700' : 'text-slate-300'}">${h.gram > 0 ? h.gram.toLocaleString('id-ID') : '-'}</td>
                <td class="px-3 py-1.5 text-right ${h.upah > 0 ? 'font-medium text-slate-800' : 'text-slate-300'}">${h.upah > 0 ? 'Rp ' + h.upah.toLocaleString('id-ID') : '-'}</td>
            </tr>`;
        });

        document.getElementById('modalLoading').classList.add('hidden');
        document.getElementById('modalContent').classList.remove('hidden');
    })
    .catch(e => {
        alert('Gagal memuat data: ' + e.message);
        closeDetailModal();
    });
}

function formatTgl(tgl) {
    const d = new Date(tgl);
    const days = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    return `${days[d.getDay()]}, ${d.getDate()} ${months[d.getMonth()]}`;
}

function updateTotalPreview() {
    const bpjs    = parseInt(document.getElementById('inputBpjs').value)    || 0;
    const potLain = parseInt(document.getElementById('inputPotLain').value)  || 0;
    const tambah  = parseInt(document.getElementById('inputTambahan').value) || 0;
    const total   = currentTotalUpah + tambah - bpjs - potLain;
    document.getElementById('modalTotalAkhir').textContent = 'Rp ' + total.toLocaleString('id-ID');
}

['inputBpjs','inputPotLain','inputTambahan'].forEach(id => {
    document.getElementById(id).addEventListener('input', updateTotalPreview);
});

function saveRekap() {
    if (!currentRekapId) return;

    const saveBtn = document.querySelector('button[onclick="saveRekap()"]');
    const originalText = saveBtn?.textContent || 'Simpan';
    if (saveBtn) {
        saveBtn.disabled = true;
        saveBtn.textContent = 'Menyimpan...';
    }

    const payload = {
        potongan_bpjs: parseInt(document.getElementById('inputBpjs').value)    || 0,
        potongan_lain: parseInt(document.getElementById('inputPotLain').value)  || 0,
        tambahan:      parseInt(document.getElementById('inputTambahan').value) || 0,
        keterangan:    document.getElementById('inputKeterangan').value,
    };

    fetch(`${boronganBaseUrl}/rekap/${currentRekapId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: new URLSearchParams({ ...payload, _method: 'PUT' }).toString(),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const total = data.total_akhir;
            const fmt   = 'Rp ' + total.toLocaleString('id-ID');
            const bpjs  = payload.potongan_bpjs;
            const pot   = payload.potongan_lain;
            const tmb   = payload.tambahan;

            document.getElementById(`total-${currentRekapId}`).textContent = fmt;
            document.getElementById(`bpjs-${currentRekapId}`).textContent  = bpjs > 0 ? 'Rp ' + bpjs.toLocaleString('id-ID') : '-';
            document.getElementById(`pot-${currentRekapId}`).textContent   = pot  > 0 ? 'Rp ' + pot.toLocaleString('id-ID')  : '-';
            document.getElementById(`tmb-${currentRekapId}`).textContent   = tmb  > 0 ? 'Rp ' + tmb.toLocaleString('id-ID')  : '-';

            closeDetailModal();
        } else {
            alert('Gagal menyimpan.');
        }
    })
    .catch(e => alert('Error: ' + e.message))
    .finally(() => {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.textContent = originalText;
        }
    });
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.add('hidden');
    currentRekapId = null;
}

document.getElementById('detailModal').addEventListener('click', function(e) {
    if (e.target === this) closeDetailModal();
});

// Search
document.getElementById('searchRekap').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.rekap-row').forEach(row => {
        const match = (row.dataset.nip + row.dataset.nama).includes(q);
        row.style.display = match ? '' : 'none';
    });
});
</script>
@endsection
