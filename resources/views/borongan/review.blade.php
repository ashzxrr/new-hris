
@extends('layouts.app')
@section('content')
<div>
    <div class="flex items-center justify-between mb-5">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:gap-4">
            <div>
                @php
            $jenisLabels = [
                'cetak' => 'HCR',
                'moulding' => 'Moulding/Cetak',
                'cabut' => 'Cabut',
            ];
        @endphp
        <h1 class="text-xl font-semibold text-slate-800">Review Import — {{ $import->filename }}</h1>
                <p class="text-xs text-slate-400 mt-1">
                    {{ $jenisLabels[$import->jenis] ?? ucfirst($import->jenis) }} •
                    {{ \Carbon\Carbon::parse($import->tanggal_dari)->format('d M') }} —
                    {{ \Carbon\Carbon::parse($import->tanggal_sampai)->format('d M Y') }}
                </p>
            </div>
            @if($siblingImports->count() > 1)
            <div class="flex items-center gap-2">
                <label class="text-xs text-slate-400">Tanggal:</label>
                <select onchange="window.location.href = this.value" class="border border-[#E5E7EB] rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                    @foreach($siblingImports as $sibling)
                    <option value="{{ route('borongan.review', $sibling->id) }}" {{ $sibling->id === $import->id ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::parse($sibling->tanggal_dari)->translatedFormat('d F Y') }}
                        @if($sibling->status === 'approved') ✅ @elseif($sibling->total_flagged > 0) ⚠️ @endif
                    </option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
        <div class="flex gap-3">
            <a href="{{ $payrollId ? route('payroll.show', $payrollId) : route('borongan.index') }}"
                class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm">← Kembali</a>
            @if($import->status !== 'approved')
            <button type="button" onclick="openRevisiModal()"
                class="border border-amber-300 text-amber-600 px-3 py-2 rounded-lg text-sm hover:bg-amber-50">
                ✏️ Revisi
            </button>
            <a href="{{ route('borongan.exportReview', $import->id) }}"
                class="border border-[#E5E7EB] text-slate-600 px-3 py-2 rounded-lg text-sm hover:bg-[#F8FAFC]">
                📥 Export Excel
            </a>
            <form method="POST" action="{{ route('borongan.undo', $import->id) }}"
                onsubmit="return confirm('Undo upload ini? Semua data akan dihapus.')" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="border border-red-300 text-red-600 px-4 py-2 rounded-lg text-sm hover:bg-red-50">
                    ↶ Undo Upload
                </button>
            </form>

            @if(!empty($pendingMutasi) && $pendingMutasi->isNotEmpty())
                <button type="button" disabled title="Selesaikan konfirmasi mutasi dulu" class="bg-slate-200 text-slate-400 px-4 py-2 rounded-lg text-sm cursor-not-allowed">
                    ✅ Approve Semua
                </button>
            @else
            <form method="POST" action="{{ route('borongan.approve', $import->id) }}"
                onsubmit="return confirm('Approve semua data ini?')" style="display:inline;">
                @csrf @method('PUT')
                <button type="submit" class="bg-[#22C55E] text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600">
                    ✅ Approve Semua
                </button>
            </form>
            @endif
            @endif
        </div>
    </div>

    {{-- Summary --}}
    <div class="grid grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Karyawan</div>
            <div class="text-2xl font-bold text-slate-800">{{ $items->count() }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Perlu Direview</div>
            <div class="text-2xl font-bold text-amber-500">{{ $items->where('is_flagged', true)->count() }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Upah</div>
            <div class="text-xl font-bold text-[#4F46E5]">Rp {{ number_format($items->sum('total_upah'), 0, ',', '.') }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Gram</div>
            <div class="text-xl font-bold text-slate-800">{{ number_format($items->sum('total_gram')) }}</div>
        </div>
    </div>

    {{-- Search --}}
    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <label class="flex items-center gap-2 text-xs text-slate-500 cursor-pointer whitespace-nowrap">
            <input type="checkbox" id="highlightLowWage" class="rounded border-[#E5E7EB] text-amber-500 focus:ring-amber-500/30">
            Highlight upah < Rp 50.000
        </label>
        <div class="flex items-center gap-2 w-full md:w-auto">
            <select id="filterStatus" class="border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm">
                <option value="semua">Semua</option>
                <option value="ada">Yang Ada Data</option>
                <option value="kosong">Yang Kosong (Tidak Ada Data)</option>
            </select>
            <input type="text" id="searchReview" placeholder="Cari NIP atau Nama..."
                class="w-full md:w-[360px] border border-[#E5E7EB] rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
        </div>
    </div>

    {{-- Bulk action bar (hidden until selection) --}}
    <div id="bulkActionBar" class="hidden flex items-center gap-2 mb-3">
        <span class="text-xs text-slate-500"><span id="selectedCount">0</span> dipilih</span>
        <input type="number" id="bulkUpahInput" placeholder="Nominal upah, mis. 65000"
            class="border border-[#E5E7EB] rounded-lg px-3 py-1.5 text-sm w-48">
        <button type="button" onclick="applyBulkUpah()"
            class="bg-[#4F46E5] text-white px-3 py-1.5 rounded-lg text-xs hover:bg-[#4338CA]">
            Terapkan
        </button>
        <input type="number" id="bulkTrainingInput" placeholder="Target upah training, mis. 65000"
            class="border border-[#E5E7EB] rounded-lg px-3 py-1.5 text-sm w-56">
        <button type="button" onclick="applyBulkTraining()"
            class="bg-amber-500 text-white px-3 py-1.5 rounded-lg text-xs hover:bg-amber-600">
            Set Tambahan Training
        </button>
        <button type="button" onclick="hapusTerpilih()"
            class="bg-red-500 text-white px-3 py-1.5 rounded-lg text-xs hover:bg-red-600">
            🗑️ Hapus Terpilih
        </button>
    </div>

    @if(!empty($pendingMutasi) && $pendingMutasi->isNotEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
        <p class="text-sm font-medium text-amber-700">⚠️ Ada {{ $pendingMutasi->count() }} indikasi mutasi karyawan yang belum dikonfirmasi.</p>
        <p class="text-xs text-amber-600 mt-1">Karyawan berikut terdeteksi muncul di lebih dari satu jenis borongan dalam periode ini. Konfirmasi setiap kasus sebelum bisa approve.</p>
    </div>
    @endif

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-[#F8FAFC] border-b border-[#E5E7EB]">
                <tr>
                    <th class="px-3 py-2.5 w-8"><input type="checkbox" id="checkAll"></th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">NIP</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Nama</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Gram</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide cursor-pointer hover:text-[#4F46E5]" id="sortUpahHeader" onclick="toggleSortUpah()">
                        Total Upah <span id="sortUpahIcon">⇕</span>
                    </th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400 uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody id="reviewBody">
                @foreach($items as $item)
                <tr class="review-row rowClickable cursor-pointer border-b border-[#E5E7EB]/50 {{ $item['is_flagged'] ? 'bg-amber-50' : 'hover:bg-[#F8FAFC]' }}"
                    data-nip="{{ strtolower($item['nip']) }}"
                    data-nama="{{ strtolower($item['nama']) }}"
                    data-upah="{{ $item['total_upah'] }}"
                    data-status="{{ $item['is_kosong'] ? 'kosong' : 'ada' }}">
                    <td class="px-4 py-3"><input type="checkbox" class="rowCheck" value="{{ $item['nip'] }}"></td>
                    <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $item['nip'] }}</td>
                    <td class="px-4 py-3 font-medium text-slate-800">
                        <button type="button"
                            onclick="openReviewModal('{{ $import->id }}', '{{ $item['nip'] }}', '{{ addslashes($item['nama']) }}')"
                            class="text-[#4F46E5] hover:underline text-left">
                            {{ $item['nama'] }}
                        </button>
                    </td>
                    <td class="px-4 py-3 text-right text-slate-600">{{ number_format($item['total_gram']) }}</td>
                    <td class="px-4 py-3 text-right font-medium text-slate-800">Rp {{ number_format($item['total_upah'], 0, ',', '.') }}</td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $mutasiForNip = $pendingMutasi->firstWhere('nip', $item['nip'] ?? null) ?? null;
                        @endphp
                        @if($item['is_flagged'])
                            <span class="text-xs bg-[#F59E0B]/10 text-[#F59E0B] px-2 py-0.5 rounded-full font-medium">
                                ⚠️ {{ $item['flag_count'] }} flagged
                            </span>
                        @else
                            <span class="text-xs bg-[#22C55E]/10 text-[#22C55E] px-2 py-0.5 rounded-full font-medium">OK</span>
                        @endif

                        @if($mutasiForNip)
                            <button type="button" onclick="openMutasiModal({{ $mutasiForNip->id }}, '{{ $item['nip'] }}', '{{ addslashes($item['nama']) }}', '{{ $mutasiForNip->jenis_a }}', '{{ $mutasiForNip->jenis_b }}')"
                                class="text-xs bg-purple-50 text-purple-600 px-2 py-0.5 rounded-full font-medium ml-1 hover:bg-purple-100">
                                🔄 Mutasi
                            </button>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button type="button"
                            onclick="openReviewModal('{{ $import->id }}', '{{ $item['nip'] }}', '{{ addslashes($item['nama']) }}')"
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
<div id="mutasiModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-5">
        <h3 class="font-semibold text-slate-800 mb-1">Konfirmasi Mutasi</h3>
        <p class="text-sm text-slate-600 mb-4" id="mutasiModalDesc"></p>

        <button onclick="resolveMutasi('confirmed')" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-purple-700 mb-2">
            ✅ Konfirmasi Mutasi (data valid di kedua jenis)
        </button>

        <p class="text-xs text-slate-500 mt-3 mb-1">Atau tandai kesalahan input di salah satu jenis:</p>
        <div class="flex gap-2">
            <button id="mutasiWrongA" onclick="resolveMutasi('rejected', 'a')" class="flex-1 border border-red-300 text-red-600 px-3 py-2 rounded-lg text-xs hover:bg-red-50"></button>
            <button id="mutasiWrongB" onclick="resolveMutasi('rejected', 'b')" class="flex-1 border border-red-300 text-red-600 px-3 py-2 rounded-lg text-xs hover:bg-red-50"></button>
        </div>
    </div>
</div>

<div id="reviewModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-4 max-h-[85vh] overflow-y-auto">
        <div class="sticky top-0 bg-white rounded-t-2xl border-b border-[#E5E7EB] px-6 py-4 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-slate-800" id="reviewModalNama"></h3>
                <p class="text-xs text-slate-400" id="reviewModalNip"></p>
            </div>
            <button onclick="closeReviewModal()" class="text-slate-400 hover:text-slate-600 text-xl">✕</button>
        </div>
        <div class="p-6">
            <div id="reviewModalLoading" class="text-center py-8 text-slate-400 text-sm">Memuat data...</div>
            <div id="reviewModalContent" class="hidden">
                <div id="reviewModalTotalUpah" class="mb-4 text-sm font-semibold text-slate-700 hidden">
                    Total Upah (NIP ini): Rp 0
                </div>
                <table class="w-full text-xs">
                    <thead class="bg-[#F8FAFC] border-b border-[#E5E7EB]">
                        <tr>
                            <th class="px-3 py-2 text-left text-slate-400">Tanggal</th>
                            <th class="px-3 py-2 text-left text-slate-400">Kategori</th>
                            <th class="px-3 py-2 text-right text-slate-400">Gram</th>
                            <th class="px-3 py-2 text-right text-slate-400">Upah File</th>
                            <th class="px-3 py-2 text-right text-slate-400">Upah Sistem</th>
                            <th class="px-3 py-2 text-right text-slate-400">Potongan</th>
                            <th class="px-3 py-2 text-center text-slate-400" id="trainingHeader" style="display:none">Tambahan Training</th>
                            <th class="px-3 py-2 text-center text-slate-400">Status</th>
                        </tr>
                    </thead>
                    <tbody id="reviewModalBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="revisiModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-start justify-between px-5 py-4 border-b border-[#E5E7EB]">
            <div>
                <h3 class="font-semibold text-slate-800">Revisi Import — {{ \Carbon\Carbon::parse($import->tanggal_dari)->format('d M Y') }}</h3>
                <p class="text-xs text-slate-400 mt-1">Upload ulang file untuk tanggal ini saja. Data lama akan diganti.</p>
            </div>
            <button type="button" onclick="closeRevisiModal()" class="text-slate-400 hover:text-slate-600 text-xl leading-none">✕</button>
        </div>

        <form id="revisiForm" class="p-5 space-y-3" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="jenis" value="{{ $import->jenis }}">
            <input type="hidden" name="payroll_id" value="{{ $import->payroll_id }}">
            <input type="hidden" name="tanggal_dari" value="{{ $import->tanggal_dari }}">
            <input type="hidden" name="tanggal_sampai" value="{{ $import->tanggal_dari }}">
            <input type="hidden" name="confirm_revisi" value="1">

            @if($import->jenis === 'moulding')
                <div>
                    <label class="text-xs text-slate-500 mb-1 block">File Excel</label>
                    <input type="file" name="file_kategori" accept=".xlsx,.xls" required
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm">
                </div>
            @else
                <div>
                    <label class="text-xs text-slate-500 mb-1 block">File Excel</label>
                    <input type="file" name="file" accept=".xlsx,.xls" required
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm">
                </div>
            @endif

            <p class="text-[10px] text-slate-400">Pastikan sheet bernama "{{ (int) \Carbon\Carbon::parse($import->tanggal_dari)->format('d') }}" ada di file.</p>

            <div id="revisiResult" class="hidden rounded-lg border p-3 text-sm"></div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeRevisiModal()"
                    class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm">Batal</button>
                <button type="submit"
                    class="bg-amber-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-600 font-medium">
                    Upload & Parse Ulang
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const reviewBaseUrl = "{{ url('borongan') }}";
let currentReviewNip = null;
let hasUpahChanged = false;

function openReviewModal(importId, nip, nama) {
    currentReviewNip = nip.toLowerCase();
    document.getElementById('reviewModalNama').textContent = nama;
    document.getElementById('reviewModalNip').textContent = nip;
    document.getElementById('reviewModalLoading').classList.remove('hidden');
    document.getElementById('reviewModalContent').classList.add('hidden');
    document.getElementById('reviewModal').classList.remove('hidden');

    fetch(`${reviewBaseUrl}/${importId}/review-detail/${encodeURIComponent(nip)}`)
    .then(r => r.json())
    .then(data => {
        const tbody = document.getElementById('reviewModalBody');
        tbody.innerHTML = '';

        data.detail.forEach(d => {
            const tgl = formatTgl(d.tanggal);
            const hasTraining = data.detail.some(x => x.jobs.some(j => (j.tambahan_training || 0) > 0));
            if (hasTraining) {
                const hdr = document.getElementById('trainingHeader');
                if (hdr) hdr.style.display = '';
            }

            d.jobs.forEach((job, i) => {
                const flagCell = job.is_flagged
                    ? `<span class="text-xs text-amber-500" title="${job.flag_reason || ''}">⚠️</span>`
                    : `<span class="text-xs text-green-500">✅</span>`;
                const specialFlag = (job.flag_reason || '') === 'Tidak ada data pada tanggal ini';
                const actionCell = specialFlag
                    ? `<div class="flex flex-col items-center gap-1 mt-1">
                        <button type="button" onclick="konfirmasiKosong(${job.id})" class="text-[10px] bg-green-100 text-green-700 px-2 py-1 rounded hover:bg-green-200">✅ Konfirmasi Tidak Masuk</button>
                        <button type="button" onclick="hapusDariDaftar(${job.id})" class="text-[10px] bg-red-100 text-red-700 px-2 py-1 rounded hover:bg-red-200">🗑️ Hapus dari Daftar</button>
                    </div>`
                    : '';
                const selisihClass = Math.abs(job.selisih) > 1000 ? 'text-red-500' : 'text-slate-400';
                const trainingCell = hasTraining ? `<td class="px-3 py-1.5 text-green-600">${(job.tambahan_training>0)? ('Rp ' + job.tambahan_training.toLocaleString('id-ID')) : '-'}</td>` : '';
                tbody.innerHTML += `
                <tr class="job-row border-b border-[#E5E7EB]/50 ${job.is_flagged ? 'bg-amber-50' : ''}">
                    <td class="px-3 py-1.5 text-slate-600">${i === 0 ? tgl : ''}</td>
                    <td class="px-3 py-1.5 font-medium text-slate-800">${job.kategori}</td>
                    <td class="px-3 py-1.5 text-right text-slate-700">${job.gram.toLocaleString('id-ID')}</td>
                    <td class="px-3 py-1.5 text-right font-medium">Rp ${job.upah_file.toLocaleString('id-ID')}</td>
                    <td class="px-3 py-1.5 text-right">
                        <input type="number" class="w-20 px-2 py-1 border border-[#E5E7EB] rounded text-right text-sm" 
                            value="${job.upah_sistem}" data-harian-id="${job.id}" 
                            onchange="updateUpahSistem(${job.id}, this.value, '{{ $import->id }}')"/>
                    </td>
                    <td class="px-3 py-1.5 text-right font-medium text-red-500">Rp <span class="potongan-${job.id}">${job.potongan.toLocaleString('id-ID')}</span></td>
                        ${trainingCell}
                        <td class="px-3 py-1.5 text-center">
                            <div class="flex flex-col items-center">
                                ${flagCell}
                                ${actionCell}
                            </div>
                        </td>
                </tr>`;
            });
            // Subtotal per tanggal
            tbody.innerHTML += `
            <tr class="subtotal-row bg-slate-50 border-b border-[#E5E7EB]" data-tanggal="${d.tanggal}">
                <td class="px-3 py-1 text-slate-400 text-right" colspan="2"><span class="text-[10px] uppercase tracking-wide">Subtotal</span></td>
                <td class="px-3 py-1 text-right font-semibold text-slate-700 subtotal-gram">${d.total_gram.toLocaleString('id-ID')}</td>
                <td class="px-3 py-1"></td>
                <td class="px-3 py-1 text-right font-semibold text-slate-800 subtotal-upah">Rp ${d.total_upah.toLocaleString('id-ID')}</td>
                <td class="px-3 py-1" colspan="2"></td>
            </tr>`;
        });

        recalculateReviewModalTotals();
        document.getElementById('reviewModalLoading').classList.add('hidden');
        document.getElementById('reviewModalContent').classList.remove('hidden');
    })
    .catch(e => {
        alert('Gagal memuat data: ' + e.message);
        closeReviewModal();
    });
}

function formatTgl(tgl) {
    const d = new Date(tgl);
    const days = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    return `${days[d.getDay()]}, ${d.getDate()} ${months[d.getMonth()]}`;
}

function updateUpahSistem(harianId, newUpahSistem, importId) {
    fetch(`${reviewBaseUrl}/${importId}/update-upah-sistem`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({
            harian_id: harianId,
            upah_sistem: parseInt(newUpahSistem)
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            hasUpahChanged = true;

            // Update potongan display
            const potonganEl = document.querySelector(`.potongan-${harianId}`);
            if (potonganEl) {
                potonganEl.textContent = data.potongan.toLocaleString('id-ID');
            }

            // Sync main table row and import total summary
            if (currentReviewNip && data.total_upah_rekap !== undefined && data.total_upah_rekap !== null) {
                let row = null;
                try {
                    row = document.querySelector(`.review-row[data-nip="${CSS.escape(currentReviewNip)}"]`);
                } catch (error) {
                    row = null;
                }
                if (!row) {
                    row = document.querySelector(`.review-row[data-nip="${currentReviewNip}"]`);
                }

                if (row) {
                    const totalCell = row.children[3];
                    const oldTotal = parseIdNumber(totalCell?.textContent || '0');
                    const newTotal = parseIdNumber(data.total_upah_rekap);
                    const diff = newTotal - oldTotal;

                    if (totalCell) {
                        totalCell.textContent = `Rp ${newTotal.toLocaleString('id-ID')}`;
                    }

                    if (diff !== 0) {
                        const summaryCard = Array.from(document.querySelectorAll('div.grid.grid-cols-4 > div'))
                            .find(card => card.querySelector('div.text-xs')?.textContent.trim() === 'Total Upah');
                        if (summaryCard) {
                            const valueEl = summaryCard.querySelector('div.text-xl, div.text-2xl');
                            if (valueEl) {
                                const currentSummary = parseIdNumber(valueEl.textContent || '0');
                                valueEl.textContent = `Rp ${Math.max(0, currentSummary + diff).toLocaleString('id-ID')}`;
                            }
                        }
                    }
                }
            }

            recalculateReviewModalTotals();
        }
    })
    .catch(e => console.error('Error:', e));
}

let sortAscending = null; // null = default order, true = ascending, false = descending

function parseIdNumber(value) {
    return parseInt(value.toString().replace(/[^0-9-]/g, '')) || 0;
}

function toggleSortUpah() {
    const tbody = document.getElementById('reviewBody');
    const rows = Array.from(tbody.querySelectorAll('.review-row'));

    if (sortAscending === null || sortAscending === false) {
        sortAscending = true;
    } else {
        sortAscending = false;
    }

    rows.sort((a, b) => {
        const upahA = parseInt(a.dataset.upah) || 0;
        const upahB = parseInt(b.dataset.upah) || 0;
        return sortAscending ? upahA - upahB : upahB - upahA;
    });

    rows.forEach(row => tbody.appendChild(row));
    document.getElementById('sortUpahIcon').textContent = sortAscending ? '↑' : '↓';
}

function applyHighlightLowWage() {
    const checked = document.getElementById('highlightLowWage').checked;
    document.querySelectorAll('.review-row').forEach(row => {
        const upah = parseInt(row.dataset.upah) || 0;
        if (checked && upah < 50000) {
            row.classList.add('bg-red-50', 'ring-1', 'ring-red-200');
        } else {
            row.classList.remove('bg-red-50', 'ring-1', 'ring-red-200');
        }
    });
}

document.getElementById('highlightLowWage').addEventListener('change', applyHighlightLowWage);

document.getElementById('filterStatus').addEventListener('change', function() {
    const val = this.value;
    document.querySelectorAll('.rowClickable').forEach(row => {
        const status = row.dataset.status;
        row.style.display = (val === 'semua' || status === val) ? '' : 'none';
    });
});

function hapusTerpilih() {
    const nips = [...document.querySelectorAll('.rowCheck:checked')].map(cb => cb.value);
    if (nips.length === 0) return;
    if (!confirm(`Hapus ${nips.length} karyawan terpilih dari daftar tanggal ini?`)) return;

    fetch('{{ route('borongan.bulkHapusKosong', $import->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ nips })
    })
    .then(r => r.json())
    .then(data => {
        let msg = `${data.deleted} baris dihapus.`;
        const skippedNames = Object.values(data.skipped || {});
        if (skippedNames.length > 0) {
            msg += `\n\n${skippedNames.length} dilewati (punya data asli, bukan baris kosong): ${skippedNames.join(', ')}`;
        }
        alert(msg);
        location.reload();
    });
}

function recalculateReviewModalTotals() {
    const tbody = document.getElementById('reviewModalBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    let subtotalGram = 0;
    let subtotalUpah = 0;
    let totalUpah = 0;

    rows.forEach(row => {
        if (row.classList.contains('job-row')) {
            const gramText = row.children[2]?.textContent.trim() || '0';
            const upahInput = row.querySelector('input[data-harian-id]');
            const gramValue = parseIdNumber(gramText);
            const upahValue = parseIdNumber(upahInput?.value || '0');

            subtotalGram += gramValue;
            subtotalUpah += upahValue;
            totalUpah += upahValue;
        }

        if (row.classList.contains('subtotal-row')) {
            const gramCell = row.querySelector('.subtotal-gram');
            const upahCell = row.querySelector('.subtotal-upah');
            if (gramCell) {
                gramCell.textContent = subtotalGram.toLocaleString('id-ID');
            }
            if (upahCell) {
                upahCell.textContent = `Rp ${subtotalUpah.toLocaleString('id-ID')}`;
            }
            subtotalGram = 0;
            subtotalUpah = 0;
        }
    });

    const totalEl = document.getElementById('reviewModalTotalUpah');
    if (totalEl) {
        totalEl.textContent = `Total Upah (NIP ini): Rp ${totalUpah.toLocaleString('id-ID')}`;
        totalEl.classList.remove('hidden');
    }
}

function konfirmasiKosong(harianId) {
    fetch(`{{ url('borongan/harian') }}/${harianId}/konfirmasi-kosong`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(() => location.reload());
}

function hapusDariDaftar(harianId) {
    if (!confirm('Hapus karyawan ini dari daftar tanggal ini?')) return;

    fetch(`{{ url('borongan/harian') }}/${harianId}/hapus-daftar`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(() => location.reload());
}

function closeReviewModal() {
    if (hasUpahChanged) {
        window.location.reload();
        return;
    }
    document.getElementById('reviewModal').classList.add('hidden');
}

document.getElementById('reviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeReviewModal();
});

document.getElementById('searchReview').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.review-row').forEach(row => {
        const match = (row.dataset.nip + row.dataset.nama).includes(q);
        row.style.display = match ? '' : 'none';
    });
});

function resolveMutasi_old(status) {
    fetch(`{{ url('borongan/mutasi') }}/${currentMutasiLogId}/resolve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ status: status })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    })
    .catch(e => alert('Gagal: ' + e.message));
}

function applyBulkTraining() {
    const nips = [...document.querySelectorAll('.rowCheck:checked')].map(cb => cb.value);
    const target = document.getElementById('bulkTrainingInput').value;

    if (!target || nips.length === 0) return;

    fetch('{{ route('borongan.bulkTraining', $import->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ nips, target_upah: target })
    })
    .then(r => r.json())
    .then(data => {
        let msg = `${data.updated} karyawan diberi tambahan training.`;
        if (data.skipped.length) msg += ` ${data.skipped.length} dilewati (multi-kategori).`;
        alert(msg);
        location.reload();
    });
}


function openRevisiModal() {
    document.getElementById('revisiModal').classList.remove('hidden');
}

function closeRevisiModal() {
    document.getElementById('revisiModal').classList.add('hidden');
}

document.getElementById('revisiForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const resultBox = document.getElementById('revisiResult');
    const btn = this.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Memproses...';
    }

    fetch('{{ route('borongan.upload') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json'
        },
        body: new FormData(this)
    })
    .then(r => r.json().then(data => ({ ok: r.ok, data })))
    .then(({ ok, data }) => {
        if (!ok) throw new Error(data.message || 'Gagal memproses revisi.');
        resultBox.classList.remove('hidden');
        resultBox.className = 'rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700';
        resultBox.textContent = 'Revisi berhasil, halaman akan dimuat ulang...';
        setTimeout(() => location.reload(), 1200);
    })
    .catch(e => {
        resultBox.classList.remove('hidden');
        resultBox.className = 'rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-600';
        resultBox.textContent = e.message;
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'Upload & Parse Ulang';
        }
    });
});

document.getElementById('mutasiModal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});

// Bulk upah sistem UI
const checkAll = document.getElementById('checkAll');
const bulkBar = document.getElementById('bulkActionBar');
const selectedCountEl = document.getElementById('selectedCount');

function refreshBulkBar() {
    const checked = document.querySelectorAll('.rowCheck:checked');
    selectedCountEl.textContent = checked.length;
    if (bulkBar) bulkBar.classList.toggle('hidden', checked.length === 0);
}

if (checkAll) {
    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.rowCheck').forEach(cb => cb.checked = this.checked);
        refreshBulkBar();
    });
}

document.querySelectorAll('.rowCheck').forEach(cb => cb.addEventListener('change', refreshBulkBar));

document.querySelectorAll('.rowClickable').forEach(row => {
    row.addEventListener('click', function (e) {
        if (e.target.closest('.rowCheck') || e.target.closest('button') || e.target.closest('a')) return;

        const checkbox = this.querySelector('.rowCheck');
        checkbox.checked = !checkbox.checked;
        refreshBulkBar();
    });
});

function applyBulkUpah() {
    const nips = [...document.querySelectorAll('.rowCheck:checked')].map(cb => cb.value);
    const nominal = document.getElementById('bulkUpahInput').value;

    if (!nominal || nips.length === 0) return;

    fetch('{{ route('borongan.bulkUpahSistem', $import->id) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ nips, upah_sistem: nominal })
    })
    .then(r => r.json())
    .then(data => {
        let msg = `${data.updated} karyawan diperbarui.`;
        if (data.skipped && data.skipped.length) msg += ` ${data.skipped.length} dilewati (multi-kategori, edit manual via Detail).`;
        alert(msg);
        location.reload();
    })
    .catch(e => alert('Gagal: ' + e.message));
}

let currentMutasiLogId = null;

function openMutasiModal(logId, nip, nama, jenisA, jenisB) {
    currentMutasiLogId = logId;
    document.getElementById('mutasiModalDesc').textContent = 
        `${nama} (${nip}) terdeteksi muncul di jenis "${jenisA.toUpperCase()}" DAN "${jenisB.toUpperCase()}" dalam periode ini. Apakah ini benar (mutasi/pindah jenis kerja), atau ini kesalahan input?`;
    document.getElementById('mutasiWrongA').textContent = `❌ Salah di ${jenisA.toUpperCase()}`;
    document.getElementById('mutasiWrongB').textContent = `❌ Salah di ${jenisB.toUpperCase()}`;
    document.getElementById('mutasiModal').classList.remove('hidden');
}

function resolveMutasi(status, wrongSide = null) {
    fetch(`{{ url('borongan/mutasi') }}/${currentMutasiLogId}/resolve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ status: status, wrong_side: wrongSide })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) window.location.reload();
        else alert(data.message || 'Gagal menyimpan.');
    })
    .catch(e => alert('Gagal: ' + e.message));
}
</script>
@endsection
