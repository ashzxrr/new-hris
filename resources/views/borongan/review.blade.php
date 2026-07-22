
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
                <select onchange="window.location.href = this.value" class="px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
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
                class="pbtn pbtn-secondary">
                <span class="pbtn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                </span>
                <span>Kembali</span></a>
            @if($import->status !== 'approved')
            <button type="button" onclick="openRevisiModal()"
                class="pbtn pbtn-warning">
                <span class="pbtn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </span>
                <span>Revisi</span>
            </button>
            <a href="{{ route('borongan.exportReview', $import->id) }}"
                class="pbtn pbtn-secondary">
                <span class="pbtn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                </span>
                <span>Export Excel</span>
            </a>
            <form method="POST" action="{{ route('borongan.undo', $import->id) }}"
                onsubmit="return confirm('Undo upload ini? Semua data akan dihapus.')" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="pbtn pbtn-danger">
                    <span class="pbtn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
                    </span>
                    <span>Undo Upload</span>
                </button>
            </form>

            @if(!empty($pendingMutasi) && $pendingMutasi->isNotEmpty())
                <button type="button" disabled title="Selesaikan konfirmasi mutasi dulu" class="pbtn pbtn-secondary">
                    <span class="pbtn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                    </span>
                    <span>Approve Semua</span>
                </button>
            @else
            <form method="POST" action="{{ route('borongan.approve', $import->id) }}"
                onsubmit="return confirm('Approve semua data ini?')" style="display:inline;">
                @csrf @method('PUT')
                <button type="submit" class="pbtn pbtn-success">
                    <span class="pbtn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                    </span>
                    <span>Approve Semua</span>
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
            <select id="filterStatus" class="px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
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
            class="pbtn pbtn-primary pbtn-sm">
            Terapkan
        </button>
        <input type="number" id="bulkTrainingInput" placeholder="Target upah training, mis. 65000"
            class="border border-[#E5E7EB] rounded-lg px-3 py-1.5 text-sm w-56">
        <button type="button" onclick="applyBulkTraining()"
            class="pbtn pbtn-warning pbtn-sm">
            Set Tambahan Training
        </button>
        <button type="button" onclick="hapusTerpilih()"
            class="pbtn pbtn-danger pbtn-sm">
            <span class="pbtn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M6 6l1 14h10l1-14"/></svg>
            </span>
            <span>Hapus Terpilih</span>
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

        <button onclick="resolveMutasi('confirmed')" class="w-full pbtn pbtn-success mb-2">
            ✅ Konfirmasi Mutasi (data valid di kedua jenis)
        </button>

        <p class="text-xs text-slate-500 mt-3 mb-1">Atau tandai kesalahan input di salah satu jenis:</p>
        <div class="flex gap-2">
            <button id="mutasiWrongA" onclick="resolveMutasi('rejected', 'a')" class="flex-1 pbtn pbtn-danger pbtn-sm"></button>
            <button id="mutasiWrongB" onclick="resolveMutasi('rejected', 'b')" class="flex-1 pbtn pbtn-danger pbtn-sm"></button>
        </div>
    </div>
</div>

<div id="reviewModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-4 max-h-[85vh] overflow-y-auto">
        <div class="sticky top-0 bg-white rounded-t-2xl border-b border-[#E5E7EB] px-6 py-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="font-semibold text-slate-800" id="reviewModalNama"></h3>
                <p class="text-xs text-slate-400" id="reviewModalNip"></p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="openAddGramModal()" class="pbtn pbtn-secondary pbtn-sm">
                    <span class="pbtn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </span>
                    <span>Tambah Gram</span>
                </button>
                <button onclick="closeReviewModal()" class="text-slate-400 hover:text-slate-600 text-xl">✕</button>
            </div>
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
                            <th class="px-3 py-2 text-left text-slate-400">Catatan</th>
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

<div id="addGramModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-5 py-4 border-b border-[#E5E7EB]">
            <div>
                <h3 class="font-semibold text-slate-800">Tambah Gram Tambahan</h3>
                <p class="text-xs text-slate-500" id="addGramNama"></p>
            </div>
            <button type="button" onclick="closeAddGramModal()" class="text-slate-400 hover:text-slate-600 text-xl">✕</button>
        </div>
        <form onsubmit="submitAddGramForm(event)" class="p-5 space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-xs text-slate-500 mb-1 block">NIP</label>
                    <input id="addGramNip" type="text" readonly class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm bg-slate-100" />
                </div>
                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Tanggal</label>
                    <input id="addGramTanggal" type="date" class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm" required />
                </div>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Gram Tambahan</label>
                    <input id="addGramBerat" type="number" min="1" class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm" placeholder="Mis. 100" required />
                </div>
                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Catatan</label>
                    <input id="addGramNote" type="text" class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm" placeholder="Contoh: Gram tambahan" />
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeAddGramModal()" class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm">Batal</button>
                <button type="submit" class="bg-[#4F46E5] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#4338CA]">Simpan Gram</button>
            </div>
        </form>
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
const mutasiResolveBaseUrl = "{{ url('borongan/mutasi') }}";
let currentReviewNip = null;
let hasUpahChanged = false;

function openReviewModal(importId, nip, nama) {
    currentReviewImportId = importId;
    currentReviewNip = nip.toLowerCase();
    currentReviewName = nama;
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
                        <button type="button" onclick="konfirmasiKosong(${job.id})" class="pbtn pbtn-success pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><polyline points="20 6 9 17 4 12"/></svg>
                            </span>
                            <span>Konfirmasi</span>
                        </button>
                        <button type="button" onclick="hapusDariDaftar(${job.id})" class="pbtn pbtn-danger pbtn-sm">
                            <span class="pbtn-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3 h-3"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                            </span>
                            <span>Hapus</span>
                        </button>
                    </div>`
                    : '';
                const selisihClass = Math.abs(job.selisih) > 1000 ? 'text-red-500' : 'text-slate-400';
                const additionBadge = job.is_additional ? '<span class="text-xs bg-slate-100 text-slate-700 px-2 py-0.5 rounded-full">Tambahan</span>' : ''; 
                const trainingCell = hasTraining ? `<td class="px-3 py-1.5 text-green-600">${(job.tambahan_training>0)? ('Rp ' + job.tambahan_training.toLocaleString('id-ID')) : '-'}</td>` : '';
                tbody.innerHTML += `
                <tr class="job-row border-b border-[#E5E7EB]/50 ${job.is_flagged ? 'bg-amber-50' : job.is_additional ? 'bg-slate-50' : ''}">
                    <td class="px-3 py-1.5 text-slate-600">${i === 0 ? tgl : ''}</td>
                    <td class="px-3 py-1.5 font-medium text-slate-800">${job.kategori} ${additionBadge}</td>
                    <td class="px-3 py-1.5 text-right text-slate-700">${job.gram.toLocaleString('id-ID')}</td>
                    <td class="px-3 py-1.5 text-left text-slate-700">${job.gram_note ? job.gram_note : '-'}</td>
                    <td class="px-3 py-1.5 text-right font-medium">Rp ${job.upah_file.toLocaleString('id-ID')}</td>
                    <td class="px-3 py-1.5 text-right">Rp ${job.upah_sistem.toLocaleString('id-ID')}</td>
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
                <td class="px-3 py-1 text-slate-400 text-right" colspan="3"><span class="text-[10px] uppercase tracking-wide">Subtotal</span></td>
                <td class="px-3 py-1 text-right font-semibold text-slate-700 subtotal-gram">${d.total_gram.toLocaleString('id-ID')}</td>
                <td class="px-3 py-1"></td>
                <td class="px-3 py-1"></td>
                <td class="px-3 py-1 text-right font-semibold text-slate-800 subtotal-upah">Rp ${d.total_upah.toLocaleString('id-ID')}</td>
                <td class="px-3 py-1"></td>
                <td class="px-3 py-1"></td>
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

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 z-50 max-w-xs rounded-xl px-4 py-3 text-sm font-medium shadow-lg transition duration-200';
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(-8px)';

    if (type === 'success') {
        toast.classList.add('bg-emerald-600', 'text-white');
    } else if (type === 'error') {
        toast.classList.add('bg-red-600', 'text-white');
    } else {
        toast.classList.add('bg-slate-800', 'text-white');
    }

    toast.textContent = message;
    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
    });

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        toast.addEventListener('transitionend', () => toast.remove(), { once: true });
    }, 2200);
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
            const gramCell = row.children[2];
            const upahInput = row.querySelector('input[data-field="upah_sistem"]');
            const gramValue = parseIdNumber(gramCell?.textContent || '0');
            const upahValue = parseIdNumber(upahInput?.value || upahInput?.textContent || '0');

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

let currentReviewImportId = null;
let currentReviewName = null;

function openAddGramModal() {
    const modal = document.getElementById('addGramModal');
    if (!modal) return;

    document.getElementById('addGramNip').value = currentReviewNip || '';
    document.getElementById('addGramNama').textContent = currentReviewName || '';
    document.getElementById('addGramTanggal').value = new Date().toISOString().slice(0, 10);
    document.getElementById('addGramBerat').value = '';
    document.getElementById('addGramNote').value = '';
    modal.classList.remove('hidden');
}

function closeAddGramModal() {
    const modal = document.getElementById('addGramModal');
    if (!modal) return;
    modal.classList.add('hidden');
}

function submitAddGramForm(event) {
    event.preventDefault();
    const importId = currentReviewImportId;
    if (!importId) return;

    const nip = document.getElementById('addGramNip').value;
    const tanggal = document.getElementById('addGramTanggal').value;
    const beratGram = parseInt(document.getElementById('addGramBerat').value) || 0;
    const gramNote = document.getElementById('addGramNote').value || null;

    if (!nip || !tanggal || beratGram <= 0) {
        alert('Lengkapi NIP, tanggal, dan nominal gram tambahan.');
        return;
    }

    fetch(`${reviewBaseUrl}/${importId}/add-gram`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({
            nip,
            tanggal,
            berat_gram: beratGram,
            gram_note: gramNote,
        })
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Gagal menambahkan gram tambahan.');
            return;
        }

        closeAddGramModal();
        showToast('Gram tambahan berhasil disimpan.', 'success');
        const row = document.querySelector(`.review-row[data-nip="${currentReviewNip}"]`);
        const oldTotal = row ? parseIdNumber(row.children[3]?.textContent || '0') : 0;
        const newTotal = data.total_gram ?? oldTotal;
        const diff = newTotal - oldTotal;

        if (row) {
            row.children[3].textContent = newTotal.toLocaleString('id-ID');
        }

        const totalGramCard = Array.from(document.querySelectorAll('div.grid.grid-cols-4 > div'))
            .find(card => card.querySelector('div.text-xs')?.textContent.trim() === 'Total Gram');
        if (totalGramCard) {
            const valueEl = totalGramCard.querySelector('div.text-xl, div.text-2xl');
            if (valueEl) {
                const currentValue = parseIdNumber(valueEl.textContent || '0');
                valueEl.textContent = (currentValue + diff).toLocaleString('id-ID');
            }
        }

        openReviewModal(importId, currentReviewNip, currentReviewName);
    })
    .catch(e => {
        console.error(e);
        alert('Gagal menambahkan gram tambahan.');
    });
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
    if (!currentMutasiLogId) {
        return alert('ID mutasi tidak valid.');
    }

    const resolveUrl = `${mutasiResolveBaseUrl}/${encodeURIComponent(currentMutasiLogId)}/resolve`;

    fetch(resolveUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ status: status, wrong_side: wrongSide })
    })
    .then(response => response.text().then(text => {
        const contentType = response.headers.get('content-type') || '';
        if (!response.ok) {
            console.error('Mutasi resolve failed', { status: response.status, body: text });
            throw new Error(text || `HTTP ${response.status}`);
        }
        if (!contentType.includes('application/json')) {
            console.error('Unexpected mutasi response content-type', { contentType, body: text });
            throw new Error(text || 'Unexpected response from server');
        }
        return JSON.parse(text);
    }))
    .then(data => {
        if (data.success) window.location.reload();
        else alert(data.message || 'Gagal menyimpan.');
    })
    .catch(e => alert('Gagal: ' + e.message));
}
</script>
@endsection
