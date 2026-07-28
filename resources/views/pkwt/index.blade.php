@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-semibold text-slate-800">Export PKWT</h1>
    <div class="flex items-center gap-2">
        <span id="selectedCount" class="text-xs text-slate-400 hidden">
            <span id="selectedNum">0</span> dipilih
        </span>
        <button type="button" id="btnExportBulk" onclick="openBulkExportModal()" disabled
            class="pbtn pbtn-primary disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="pbtn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </span>
            <span>Proceed Export</span>
        </button>
        <a href="{{ route('pkwt.riwayat') }}" class="pbtn pbtn-secondary">
            <span class="pbtn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <span>Riwayat PKWT</span>
        </a>
    </div>
</div>

{{-- Search --}}
<div class="mb-4">
    <input
        type="text"
        id="searchInput"
        value="{{ request('search') }}"
        placeholder="Cari nama atau NIP..."
        autocomplete="off"
        class="w-full max-w-md px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition"
    >
</div>

{{-- Table --}}
<div class="bg-white rounded-xl border border-[#E5E7EB] overflow-hidden">
    <div class="overflow-auto">
        <table class="w-full text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-[#F8FAFC] text-[11px] font-medium text-slate-400 uppercase tracking-wide">
                    <th class="px-4 py-3 text-left w-10">
                        <input type="checkbox" id="checkAll" class="accent-[#4F46E5]" onchange="toggleAll(this)">
                    </th>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left">NIP</th>
                    <th class="px-4 py-3 text-left">Bagian</th>
                    <th class="px-4 py-3 text-center">Status PKWT</th>
                    <th class="px-4 py-3 text-center w-40">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($karyawan as $k)
                    @php
                        $lastExport = $lastExports[$k->id] ?? null;
                    @endphp
                    <tr class="pkwt-row hover:bg-[#F8FAFC] transition-colors"
                        data-search="{{ strtolower($k->nama . ' ' . ($k->nip ?? '') . ' ' . ($k->bagian ?? '')) }}">
                        <td class="px-4 py-2.5" onclick="event.stopPropagation()">
                            <input type="checkbox" class="pkwt-check accent-[#4F46E5]" value="{{ $k->id }}" onchange="onCheckChange(this)">
                        </td>
                        <td class="px-4 py-2.5 font-medium text-slate-800">{{ $k->nama }}</td>
                        <td class="px-4 py-2.5 text-slate-500">{{ $k->nip ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-slate-500">{{ $k->bagian ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-center">
                            @if($lastExport)
                                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2.5 py-0.5">
                                    <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    {{ $lastExport->nomor_surat }}
                                </span>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    {{ $lastExport->created_at->format('d/m/Y') }}
                                </div>
                            @else
                                <span class="text-[11px] text-slate-400 italic">Belum pernah export</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <button type="button"
                                onclick="openExportModal({{ $k->id }}, '{{ addslashes($k->nama) }}')"
                                class="pbtn pbtn-primary pbtn-sm">
                                <span class="pbtn-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                </span>
                                <span>Export PKWT</span>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">
                            Tidak ada karyawan ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal Form Export (Single) --}}
<div id="exportModal" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <div>
                <h3 class="text-base font-semibold text-slate-800">Export PKWT</h3>
                <p id="modalKaryawanName" class="text-sm text-slate-500 mt-0.5"></p>
            </div>
            <button type="button" onclick="closeExportModal()" class="text-slate-400 hover:text-slate-600">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="exportForm" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" id="formUserId" name="user_id">

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" id="tanggalMulai" required
                        class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] transition">
                </div>
                <div class="flex flex-col">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" id="tanggalSelesai" required
                        class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] transition">
                </div>
            </div>

            <div class="bg-[#F8FAFC] rounded-lg p-3 text-xs text-slate-500 space-y-1">
                <p><span class="font-medium">Tempat:</span> Lamongan</p>
                <p><span class="font-medium">Tanggal Pembuatan:</span> <span id="tglDibuatText">—</span></p>
                <p class="text-[11px] text-slate-400 italic">*Pembuatan mengikuti tanggal mulai kontrak</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeExportModal()" class="pbtn pbtn-ghost">Batal</button>
                <button type="submit" class="pbtn pbtn-primary">
                    <span class="pbtn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </span>
                    <span>Generate & Download</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Modal Form Export (Bulk) --}}
<div id="bulkExportModal" class="fixed inset-0 z-50 hidden bg-black/40 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <div>
                <h3 class="text-base font-semibold text-slate-800">Export PKWT Massal</h3>
                <p id="bulkModalCount" class="text-sm text-slate-500 mt-0.5"></p>
            </div>
            <button type="button" onclick="closeBulkExportModal()" class="text-slate-400 hover:text-slate-600">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="bulkExportForm" method="POST" action="{{ route('pkwt.exportBulk') }}" class="p-6 space-y-4">
            @csrf
            <div id="bulkExportInputs"></div>

            <div class="grid grid-cols-2 gap-4">
                <div class="flex flex-col">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" id="bulkTanggalMulai" required
                        class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] transition">
                </div>
                <div class="flex flex-col">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" id="bulkTanggalSelesai" required
                        class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] transition">
                </div>
            </div>

            <div class="bg-[#F8FAFC] rounded-lg p-3 text-xs text-slate-500 space-y-1">
                <p><span class="font-medium">Tempat:</span> Lamongan</p>
                <p><span class="font-medium">Tanggal Pembuatan:</span> <span id="bulkTglDibuatText">—</span></p>
                <p class="text-[11px] text-slate-400 italic">*Pembuatan mengikuti tanggal mulai kontrak</p>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeBulkExportModal()" class="pbtn pbtn-ghost">Batal</button>
                <button type="submit" class="pbtn pbtn-primary">
                    <span class="pbtn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </span>
                    <span>Generate & Download Semua</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Track selected user IDs (persists across search filtering)
    let selectedIds = new Set();

    // ========== Client-side Search ==========
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');

        // If there's an initial search value from server, apply it
        if (searchInput.value) {
            filterTable(searchInput.value);
        }

        searchInput.addEventListener('input', function() {
            filterTable(this.value);
        });
    });

    function filterTable(query) {
        const q = query.toLowerCase().trim();
        document.querySelectorAll('.pkwt-row').forEach(row => {
            const searchData = row.dataset.search || '';
            row.style.display = (!q || searchData.includes(q)) ? '' : 'none';
        });
        // Restore checked state from selectedIds
        document.querySelectorAll('.pkwt-check').forEach(cb => {
            cb.checked = selectedIds.has(parseInt(cb.value));
        });
        updateSelectAllState();
        updateSelectedCount();
    }

    // ========== Checkbox Handling ==========
    function onCheckChange(cb) {
        const id = parseInt(cb.value);
        if (cb.checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        updateSelectAllState();
        updateSelectedCount();
    }

    function toggleAll(master) {
        document.querySelectorAll('.pkwt-row:not([style*="display: none"]) .pkwt-check').forEach(cb => {
            const id = parseInt(cb.value);
            cb.checked = master.checked;
            if (master.checked) {
                selectedIds.add(id);
            } else {
                selectedIds.delete(id);
            }
        });
        updateSelectedCount();
    }

    function updateSelectAllState() {
        const visibleCheckboxes = document.querySelectorAll('.pkwt-row:not([style*="display: none"]) .pkwt-check');
        const checkedVisible = document.querySelectorAll('.pkwt-row:not([style*="display: none"]) .pkwt-check:checked');
        const checkAll = document.getElementById('checkAll');
        if (checkAll) {
            checkAll.checked = visibleCheckboxes.length > 0 && visibleCheckboxes.length === checkedVisible.length;
            checkAll.indeterminate = checkedVisible.length > 0 && checkedVisible.length < visibleCheckboxes.length;
        }
    }

    function updateSelectedCount() {
        const count = selectedIds.size;
        const countEl = document.getElementById('selectedNum');
        const countWrapper = document.getElementById('selectedCount');
        if (countEl) countEl.textContent = count;

        if (count > 0) {
            countWrapper?.classList.remove('hidden');
        } else {
            countWrapper?.classList.add('hidden');
        }

        const btnExport = document.getElementById('btnExportBulk');
        if (btnExport) {
            btnExport.disabled = count === 0;
        }
    }

    // ========== Single Export Modal ==========
    const exportModal = document.getElementById('exportModal');
    const exportForm = document.getElementById('exportForm');
    const formUserId = document.getElementById('formUserId');
    const modalKaryawanName = document.getElementById('modalKaryawanName');
    const tanggalMulai = document.getElementById('tanggalMulai');
    const tglDibuatText = document.getElementById('tglDibuatText');

    function openExportModal(userId, nama) {
        formUserId.value = userId;
        modalKaryawanName.textContent = nama;
        exportForm.action = `{{ url('pkwt') }}/${userId}/export`;
        exportForm.reset();
        tglDibuatText.textContent = '—';
        exportModal.classList.remove('hidden');
    }

    function closeExportModal() {
        exportModal.classList.add('hidden');
    }

    tanggalMulai?.addEventListener('change', function() {
        if (this.value) {
            const d = new Date(this.value + 'T00:00:00');
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            tglDibuatText.textContent = d.toLocaleDateString('id-ID', options);
        } else {
            tglDibuatText.textContent = '—';
        }
    });

    exportModal?.addEventListener('click', function(e) {
        if (e.target === this) closeExportModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeExportModal();
            closeBulkExportModal();
        }
    });

    // ========== Bulk Export Modal ==========
    const bulkExportModal = document.getElementById('bulkExportModal');
    const bulkTanggalMulai = document.getElementById('bulkTanggalMulai');
    const bulkTglDibuatText = document.getElementById('bulkTglDibuatText');

    function openBulkExportModal() {
        const ids = Array.from(selectedIds);
        if (ids.length === 0) return;

        // Populate hidden inputs
        const container = document.getElementById('bulkExportInputs');
        container.innerHTML = '';
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = id;
            container.appendChild(input);
        });

        document.getElementById('bulkModalCount').textContent = ids.length + ' karyawan dipilih';
        bulkExportForm.reset();
        bulkTglDibuatText.textContent = '—';
        bulkExportModal.classList.remove('hidden');
    }

    function closeBulkExportModal() {
        bulkExportModal.classList.add('hidden');
    }

    bulkTanggalMulai?.addEventListener('change', function() {
        if (this.value) {
            const d = new Date(this.value + 'T00:00:00');
            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            bulkTglDibuatText.textContent = d.toLocaleDateString('id-ID', options);
        } else {
            bulkTglDibuatText.textContent = '—';
        }
    });

    bulkExportModal?.addEventListener('click', function(e) {
        if (e.target === this) closeBulkExportModal();
