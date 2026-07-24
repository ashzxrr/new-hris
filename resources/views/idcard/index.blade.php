@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-semibold text-slate-800">Export ID Card</h1>
</div>

{{-- Filter Bagian (combo-box: ketik atau pilih) --}}
<div class="mb-4">
    <form method="GET" action="{{ route('id-card.index') }}" id="filterForm">
        <div class="grid grid-cols-3 gap-4">
            <div class="flex flex-col">
                <label class="text-xs text-slate-400 uppercase tracking-wide font-semibold block mb-1">Bagian</label>
                <div class="flex items-center gap-2">
                    <div class="relative flex-1 max-w-xs">
                        <input
                            id="filterBagian"
                            name="bagian"
                            list="bagianList"
                            value="{{ request('bagian') }}"
                            placeholder="Ketik atau pilih bagian..."
                            autocomplete="off"
                            class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition"
                        >
                        <datalist id="bagianList">
                            @foreach($bagianList as $b)
                                <option value="{{ $b }}">
                            @endforeach
                        </datalist>
                    </div>
                    <button type="submit"
                        class="pbtn pbtn-primary pbtn-sm">
                        <span class="pbtn-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </span>
                        <span>Cari</span>
                    </button>
                    @if(request('bagian') || request('status_cetak') || request('baru'))
                        <a href="{{ route('id-card.index') }}" class="pbtn pbtn-ghost pbtn-sm">
                            <span>Reset</span>
                        </a>
                    @endif
                </div>
            </div>
            <div class="flex flex-col">
                <label class="text-xs text-slate-400 uppercase tracking-wide font-semibold block mb-1">Status ID Card</label>
                <select name="status_cetak" onchange="this.form.submit()"
                    class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] transition">
                    <option value="">Semua</option>
                    <option value="belum" {{ request('status_cetak') == 'belum' ? 'selected' : '' }}>Belum Cetak</option>
                    <option value="sudah" {{ request('status_cetak') == 'sudah' ? 'selected' : '' }}>Sudah Cetak</option>
                </select>
            </div>
            <div class="flex flex-col">
                <label class="text-xs text-slate-400 uppercase tracking-wide font-semibold block mb-1">Karyawan Baru</label>
                <select name="baru" onchange="this.form.submit()"
                    class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] transition">
                    <option value="">Semua</option>
                    <option value="7" {{ request('baru') == '7' ? 'selected' : '' }}>7 Hari Terakhir</option>
                    <option value="14" {{ request('baru') == '14' ? 'selected' : '' }}>14 Hari Terakhir</option>
                    <option value="30" {{ request('baru') == '30' ? 'selected' : '' }}>30 Hari Terakhir</option>
                    <option value="90" {{ request('baru') == '90' ? 'selected' : '' }}>90 Hari Terakhir</option>
                </select>
            </div>
        </div>
    </form>
</div>

{{-- Form Export --}}
<form method="POST" action="{{ route('id-card.export') }}" target="_blank" id="exportForm">
    @csrf
    <div class="bg-white rounded-xl border border-[#E5E7EB] overflow-hidden">
        <div class="overflow-auto max-h-[70vh]">
            <style>
                .idcard-row:hover { background-color: #f0fdf4 !important; }
                .idcard-row:hover td { background-color: #f0fdf4 !important; }
                .idcard-row.checked { background-color: #dcfce7 !important; }
                .idcard-row.checked td { background-color: #dcfce7 !important; }
            </style>
            <table class="w-full text-xs whitespace-nowrap">
                <thead>
                    <tr class="sticky top-0 bg-[#F8FAFC] z-20 text-[11px] font-medium text-slate-400 uppercase tracking-wide">
                        <th class="px-2 py-2 sticky left-0 bg-[#F8FAFC] z-20 border-r border-[#E5E7EB] w-8">
                            <input type="checkbox" id="selectAll" class="accent-[#4F46E5]">
                        </th>
                        <th class="px-2 py-2 text-left">Nama</th>
                        <th class="px-2 py-2 text-left">NIP</th>
                        <th class="px-2 py-2 text-left">Bagian</th>
                        <th class="px-2 py-2 text-center w-20">ID Card</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($karyawan as $k)
                        <tr class="idcard-row border-t border-slate-50 transition-colors duration-100 cursor-pointer" data-index="{{ $loop->index }}">
                            <td class="px-2 py-1.5 sticky left-0 bg-white z-10 border-r border-[#E5E7EB]">
                                <input type="checkbox" name="id[]" value="{{ $k->id }}"
                                    class="row-checkbox accent-[#4F46E5]">
                            </td>
                            <td class="px-2 py-1.5 font-medium text-slate-800">{{ $k->nama }}</td>
                            <td class="px-2 py-1.5 text-slate-600">{{ $k->nip }}</td>
                            <td class="px-2 py-1.5 text-slate-600">{{ $k->bagian ?? '-' }}</td>
                            <td class="px-2 py-1.5 text-center">
                                @if($k->id_card_printed_at)
                                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2.5 py-0.5">
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                        Cetak
                                    </span>
                                @else
                                    <span class="text-[11px] text-slate-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">
                                Tidak ada karyawan ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 flex items-center justify-between">
        <span class="text-xs text-slate-400" id="selectedCount">0 karyawan dipilih</span>
        <button type="submit" class="pbtn pbtn-primary">
            <span class="pbtn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </span>
            <span>Export ID Card</span>
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    const rows = document.querySelectorAll('.idcard-row');
    const selectedCount = document.getElementById('selectedCount');
    let lastCheckedIndex = null;

    function syncUI() {
        let checkedCount = 0;
        let allChecked = true;
        checkboxes.forEach((cb, i) => {
            if (cb.checked) {
                checkedCount++;
                rows[i].classList.add('checked');
            } else {
                allChecked = false;
                rows[i].classList.remove('checked');
            }
        });
        selectAll.checked = allChecked && checkboxes.length > 0;
        selectedCount.textContent = checkedCount + ' karyawan dipilih';
    }

    function toggleCheck(cb, state) {
        if (state === undefined) state = !cb.checked;
        cb.checked = state;
        syncUI();
    }

    // Select All
    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        syncUI();
    });

    // Checkbox change
    checkboxes.forEach((cb, i) => {
        cb.addEventListener('change', function(e) {
            e.stopPropagation();
            syncUI();
        });
    });

    // Click row = toggle checkbox
    rows.forEach((row, i) => {
        row.addEventListener('click', function(e) {
            // Abaikan jika klik di dalam checkbox itu sendiri
            if (e.target.tagName === 'INPUT' && e.target.type === 'checkbox') return;

            const cb = checkboxes[i];

            // Shift-click: range select
            if (e.shiftKey && lastCheckedIndex !== null && lastCheckedIndex !== i) {
                const start = Math.min(lastCheckedIndex, i);
                const end = Math.max(lastCheckedIndex, i);
                const targetState = checkboxes[lastCheckedIndex].checked;
                for (let j = start; j <= end; j++) {
                    checkboxes[j].checked = targetState;
                }
                syncUI();
                return;
            }

            // Normal click: toggle
            toggleCheck(cb);
            lastCheckedIndex = i;
        });
    });

    // Auto-submit filter on Enter
    document.getElementById('filterBagian').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.form.submit();
        }
    });

    syncUI();
</script>
@endpush