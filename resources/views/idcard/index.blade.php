@php use SimpleSoftwareIO\QrCode\Facades\QrCode; @endphp
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
            <div class="flex flex-col">
                <label class="text-xs text-slate-400 uppercase tracking-wide font-semibold block mb-1">Kategori Gaji</label>
                <select name="kategori_gaji" onchange="this.form.submit()"
                    class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] transition">
                    <option value="">Semua</option>
                    @foreach($kategoriGajiList as $kategoriGaji)
                        <option value="{{ $kategoriGaji }}" {{ request('kategori_gaji') == $kategoriGaji ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $kategoriGaji)) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </form>
</div>

<div class="mb-4">
    <input id="searchInput" type="text" placeholder="Cari nama, NIP, atau bagian..."
        class="w-full bg-[#F8FAFC] border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm text-slate-700"
    />
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
                        @php
                            $qrValue = trim((string) $k->nip);
                            if ($qrValue === '') {
                                $qrValue = trim((string) $k->pin);
                            }
                            if ($qrValue === '') {
                                $qrValue = (string) $k->id;
                            }
                        @endphp
                        <tr class="idcard-row border-t border-slate-50 transition-colors duration-100 cursor-pointer" data-index="{{ $loop->index }}">
                            <td class="px-2 py-1.5 sticky left-0 bg-white z-10 border-r border-[#E5E7EB]">
                                <input type="checkbox" name="id[]" value="{{ $k->id }}"
                                    data-nama="{{ $k->nama }}"
                                    data-nip="{{ $k->nip }}"
                                    data-bagian="{{ $k->bagian ?? 'UMUM' }}"
                                    data-jabatan="{{ $k->job_level ?? $k->job_title ?? 'STAFF' }}"
                                    data-qr="{{ base64_encode(QrCode::format('svg')->size(200)->generate($qrValue)) }}"
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
        <div class="flex items-center gap-2">
            <button type="button" id="exportImageButton" class="pbtn pbtn-secondary">
                <span class="pbtn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                </span>
                <span>Export PNG</span>
            </button>
            <button type="button" id="exportPhotoButton" class="pbtn pbtn-secondary">
                <span class="pbtn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                </span>
                <span>PNG Foto</span>
            </button>
            <button type="submit" class="pbtn pbtn-primary">
                <span class="pbtn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                </span>
                <span>Export PDF</span>
            </button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const searchInput = document.getElementById('searchInput');
    const selectAll = document.getElementById('selectAll');
    const checkboxes = Array.from(document.querySelectorAll('.row-checkbox'));
    const rows = Array.from(document.querySelectorAll('.idcard-row'));
    const selectedCount = document.getElementById('selectedCount');
    const exportImageButton = document.getElementById('exportImageButton');
    const exportPhotoButton = document.getElementById('exportPhotoButton');
    const cardBackgroundUrl = @json(asset('images/card-bg-waj-3.png'));
    const selectedIds = new Set();
    let lastCheckedRow = null;

    function getVisibleRows() {
        return rows.filter(row => row.style.display !== 'none');
    }

    function getVisibleCheckboxes() {
        return getVisibleRows().map(row => row.querySelector('.row-checkbox')).filter(Boolean);
    }

    function updateSelectAllState() {
        const visible = getVisibleCheckboxes();
        const checkedVisible = visible.filter(cb => cb.checked).length;
        selectAll.checked = visible.length > 0 && checkedVisible === visible.length;
        selectAll.indeterminate = checkedVisible > 0 && checkedVisible < visible.length;
    }

    function updateSelectedCount() {
        selectedCount.textContent = selectedIds.size + ' karyawan dipilih';
        updateSelectAllState();
    }

    function setCheckboxState(cb, state) {
        cb.checked = state;
        const row = cb.closest('.idcard-row');
        if (row) row.classList.toggle('checked', state);
        if (state) {
            selectedIds.add(cb.value);
        } else {
            selectedIds.delete(cb.value);
        }
    }

    function syncSelectionFromState() {
        checkboxes.forEach(cb => {
            const state = selectedIds.has(cb.value);
            cb.checked = state;
            const row = cb.closest('.idcard-row');
            if (row) row.classList.toggle('checked', state);
        });
        updateSelectAllState();
    }

    function filterRows() {
        const q = searchInput?.value.toLowerCase().trim() || '';
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = q === '' || text.includes(q) ? '' : 'none';
        });
        syncSelectionFromState();
    }

    function toggleSelectAllVisible() {
        const visible = getVisibleCheckboxes();
        const allChecked = visible.length > 0 && visible.every(cb => cb.checked);
        visible.forEach(cb => setCheckboxState(cb, !allChecked));
    }

    selectAll?.addEventListener('change', function() {
        getVisibleCheckboxes().forEach(cb => setCheckboxState(cb, this.checked));
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function(e) {
            e.stopPropagation();
            setCheckboxState(this, this.checked);
            updateSelectAllState();
        });
    });

    rows.forEach(row => {
        row.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT' && e.target.type === 'checkbox') return;
            const cb = row.querySelector('.row-checkbox');
            if (!cb) return;

            if (e.shiftKey && lastCheckedRow && lastCheckedRow !== row) {
                const visible = getVisibleRows();
                const start = visible.indexOf(lastCheckedRow);
                const end = visible.indexOf(row);
                if (start !== -1 && end !== -1) {
                    const targetState = lastCheckedRow.querySelector('.row-checkbox')?.checked || false;
                    const [from, to] = start < end ? [start, end] : [end, start];
                    for (let i = from; i <= to; i++) {
                        const rangeCb = visible[i].querySelector('.row-checkbox');
                        if (rangeCb) setCheckboxState(rangeCb, targetState);
                    }
                    lastCheckedRow = row;
                    return;
                }
            }

            setCheckboxState(cb, !cb.checked);
            lastCheckedRow = row;
        });
    });

    searchInput?.addEventListener('input', filterRows);

    function loadImage(source) {
        return new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = source;
        });
    }

    function fitCanvasText(context, text, maxWidth, initialSize, weight = '600') {
        let size = initialSize;
        context.font = `${weight} ${size}px 'Times New Roman', Times, serif`;
        while (size > 10 && context.measureText(text).width > maxWidth) {
            size -= 1;
            context.font = `${weight} ${size}px 'Times New Roman', Times, serif`;
        }
    }

    async function createIdCardPng(checkbox, background, includeQr = true) {
        const scale = 120;
        const canvas = document.createElement('canvas');
        canvas.width = 5.5 * scale;
        canvas.height = 8.5 * scale;
        const context = canvas.getContext('2d');

        context.drawImage(background, 0, 0, canvas.width, canvas.height);

        if (includeQr) {
            const qr = await loadImage(`data:image/svg+xml;base64,${checkbox.dataset.qr}`);
            context.drawImage(qr, 1.88 * scale, 2.79 * scale, 1.75 * scale, 1.75 * scale);
        }
        context.textAlign = 'center';
        context.textBaseline = 'top';

        const center = canvas.width / 2;
        const nama = checkbox.dataset.nama.toUpperCase();
        fitCanvasText(context, nama, canvas.width * 0.9, 32, '800');
        context.fillStyle = '#17324D';
        context.fillText(nama, center, 4.82 * scale);

        context.font = '600 22px "Times New Roman", Times, serif';
        context.fillStyle = '#3A5468';
        context.fillText(`NIP. ${checkbox.dataset.nip}`, center, 5.23 * scale);

        const jabatan = checkbox.dataset.jabatan.toUpperCase();
        fitCanvasText(context, jabatan, canvas.width * 0.9, 31, '800');
        context.fillStyle = '#17324D';
        context.fillText(jabatan, center, 5.71 * scale);

        const bagian = checkbox.dataset.bagian.toUpperCase();
        context.font = '600 21px "Times New Roman", Times, serif';
        context.fillStyle = '#3D2A08';
        context.fillText(`Bagian: ${bagian}`, center, 6.05 * scale);

        return canvas.toDataURL('image/png');
    }

    async function exportSelectedImages(button, includeQr, filenamePrefix) {
        const selected = checkboxes.filter(checkbox => selectedIds.has(checkbox.value));
        if (!selected.length) {
            alert('Pilih minimal satu karyawan terlebih dahulu.');
            return;
        }

        const originalLabel = button.querySelector('span:last-child').textContent;
        button.disabled = true;
        button.querySelector('span:last-child').textContent = 'Menyiapkan PNG...';

        try {
            const background = await loadImage(cardBackgroundUrl);
            const images = [];
            for (const checkbox of selected) {
                images.push({
                    name: checkbox.dataset.nama,
                    data: await createIdCardPng(checkbox, background, includeQr),
                });
            }

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch('{{ route('id-card.export-image') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ id: selected.map(checkbox => checkbox.value) }),
            });
            if (!response.ok) throw new Error('Gagal menyimpan status export.');

            images.forEach((image, index) => {
                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = image.data;
                    link.download = `${filenamePrefix}-${image.name.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '').toLowerCase() || index + 1}.png`;
                    link.click();
                }, index * 150);
            });
            selected.forEach(checkbox => {
                const badge = checkbox.closest('tr')?.querySelector('td:last-child');
                if (badge) badge.innerHTML = '<span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-full px-2.5 py-0.5">Sudah diekspor</span>';
            });
        } catch (error) {
            alert(error.message || 'Export PNG gagal.');
        } finally {
            button.disabled = false;
            button.querySelector('span:last-child').textContent = originalLabel;
        }
    }

    exportImageButton?.addEventListener('click', function() {
        exportSelectedImages(this, true, 'id-card');
    });

    exportPhotoButton?.addEventListener('click', function() {
        exportSelectedImages(this, false, 'id-card-foto');
    });

    document.getElementById('filterBagian')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            this.form.submit();
        }
    });

    window.addEventListener('load', function() {
        syncSelectionFromState();
        filterRows();
    });
</script>
@endpush