
@extends('layouts.app')
@section('content')
<div>
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Review Import — {{ $import->filename }}</h1>
            <p class="text-xs text-slate-400 mt-1">
                {{ ucfirst($import->jenis) }} •
                {{ \Carbon\Carbon::parse($import->tanggal_dari)->format('d M') }} —
                {{ \Carbon\Carbon::parse($import->tanggal_sampai)->format('d M Y') }}
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('borongan.index') }}"
                class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm">← Kembali</a>
            @if($import->status !== 'approved')
            <form method="POST" action="{{ route('borongan.undo', $import->id) }}"
                onsubmit="return confirm('Undo upload ini? Semua data akan dihapus.')" style="display:inline;">
                @csrf @method('DELETE')
                <button type="submit" class="border border-red-300 text-red-600 px-4 py-2 rounded-lg text-sm hover:bg-red-50">
                    ↶ Undo Upload
                </button>
            </form>
            <form method="POST" action="{{ route('borongan.approve', $import->id) }}"
                onsubmit="return confirm('Approve semua data ini?')" style="display:inline;">
                @csrf @method('PUT')
                <button type="submit" class="bg-[#22C55E] text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600">
                    ✅ Approve Semua
                </button>
            </form>
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
    <div class="mb-4">
        <input type="text" id="searchReview" placeholder="Cari NIP atau Nama..."
            class="w-full border border-[#E5E7EB] rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-[#F8FAFC] border-b border-[#E5E7EB]">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">NIP</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Nama</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Gram</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Upah</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-slate-400 uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody id="reviewBody">
                @foreach($items as $item)
                <tr class="review-row border-b border-[#E5E7EB]/50 {{ $item['is_flagged'] ? 'bg-amber-50' : 'hover:bg-[#F8FAFC]' }}"
                    data-nip="{{ strtolower($item['nip']) }}"
                    data-nama="{{ strtolower($item['nama']) }}">
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
                        @if($item['is_flagged'])
                            <span class="text-xs bg-[#F59E0B]/10 text-[#F59E0B] px-2 py-0.5 rounded-full font-medium">
                                ⚠️ {{ $item['flag_count'] }} flagged
                            </span>
                        @else
                            <span class="text-xs bg-[#22C55E]/10 text-[#22C55E] px-2 py-0.5 rounded-full font-medium">OK</span>
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
                            <th class="px-3 py-2 text-center text-slate-400">Status</th>
                        </tr>
                    </thead>
                    <tbody id="reviewModalBody"></tbody>
                </table>
            </div>
        </div>
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
            d.jobs.forEach((job, i) => {
                const flagCell = job.is_flagged
                    ? `<span class="text-xs text-amber-500" title="${job.flag_reason || ''}">⚠️</span>`
                    : `<span class="text-xs text-green-500">✅</span>`;
                const selisihClass = Math.abs(job.selisih) > 1000 ? 'text-red-500' : 'text-slate-400';
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
                    <td class="px-3 py-1.5 text-center">${flagCell}</td>
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

function parseIdNumber(value) {
    return parseInt(value.toString().replace(/[^0-9-]/g, '')) || 0;
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
</script>
@endsection
