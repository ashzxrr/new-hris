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
            <form method="POST" action="{{ route('borongan.approve', $import->id) }}"
                onsubmit="return confirm('Approve semua data ini? Data flagged tetap akan diapprove apa adanya.')">
                @csrf @method('PUT')
                <button type="submit"
                    class="bg-[#22C55E] text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600">
                    ✅ Approve Semua
                </button>
            </form>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl p-3 mb-4">
        {{ session('success') }}
    </div>
    @endif

    {{-- Summary cards --}}
    <div class="grid grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Baris</div>
            <div class="text-2xl font-bold text-slate-800">{{ $import->total_baris }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Perlu Direview</div>
            <div class="text-2xl font-bold text-amber-500">{{ $import->total_flagged }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Upah</div>
            <div class="text-xl font-bold text-[#4F46E5]">
                Rp {{ number_format($items->sum('upah_file'), 0, ',', '.') }}
            </div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Selisih</div>
            <div class="text-xl font-bold {{ $items->sum('selisih') != 0 ? 'text-red-500' : 'text-[#22C55E]' }}">
                Rp {{ number_format($items->sum('selisih'), 0, ',', '.') }}
            </div>
        </div>
    </div>

    {{-- Search and Filter --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-4 mb-4 space-y-3">
        <div>
            <input type="text" id="searchInput" placeholder="Cari berdasarkan NIP atau Nama..."
                class="w-full border border-[#E5E7EB] rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-xs font-medium text-slate-600 mb-1 block">Dari Tanggal</label>
                <input type="date" id="dateFrom"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
            </div>
            <div>
                <label class="text-xs font-medium text-slate-600 mb-1 block">Sampai Tanggal</label>
                <input type="date" id="dateTo"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
            </div>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-[#F8FAFC] border-b border-[#E5E7EB]">
                <tr>
                    <th class="px-3 py-2.5 text-left font-semibold text-slate-400 uppercase tracking-wide">Tanggal</th>
                    <th class="px-3 py-2.5 text-left font-semibold text-slate-400 uppercase tracking-wide">NIP</th>
                    <th class="px-3 py-2.5 text-left font-semibold text-slate-400 uppercase tracking-wide">Nama</th>
                    <th class="px-3 py-2.5 text-left font-semibold text-slate-400 uppercase tracking-wide">Kategori</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Gram</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Upah Sistem</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Upah File</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Selisih</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Status</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                @foreach($items as $item)
                <tr class="border-b border-[#E5E7EB]/50 data-row {{ $item->is_flagged ? 'bg-amber-50' : 'hover:bg-[#F8FAFC]' }}" data-nip="{{ strtolower($item->nip) }}" data-nama="{{ strtolower($item->nama) }}">
                    <td class="px-3 py-2 text-slate-600">{{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}</td>
                    <td class="px-3 py-2 font-mono text-slate-500">{{ $item->nip }}</td>
                    <td class="px-3 py-2 font-medium text-slate-800">{{ $item->nama }}</td>
                    <td class="px-3 py-2 text-slate-600">{{ $item->kategori }}</td>
                    <td class="px-3 py-2 text-right text-slate-600">{{ number_format($item->berat_gram) }}</td>
                    <td class="px-3 py-2 text-right text-slate-600">Rp {{ number_format($item->upah_sistem) }}</td>
                    <td class="px-3 py-2 text-right font-medium text-slate-800">Rp {{ number_format($item->upah_file) }}</td>
                    <td class="px-3 py-2 text-right {{ abs($item->selisih) > 1000 ? 'text-red-500 font-medium' : 'text-slate-400' }}">
                        {{ $item->selisih != 0 ? 'Rp ' . number_format($item->selisih) : '-' }}
                    </td>
                    <td class="px-3 py-2 text-center">
                        @if($item->is_flagged)
                            <span class="text-xs bg-[#F59E0B]/10 text-[#F59E0B] px-2 py-0.5 rounded-full font-medium" title="{{ $item->flag_reason }}">
                                ⚠️ Flagged
                            </span>
                        @else
                            <span class="text-xs bg-[#22C55E]/10 text-[#22C55E] px-2 py-0.5 rounded-full font-medium">OK</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
function filterTable() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const rows = document.querySelectorAll('#tableBody .data-row');
    
    rows.forEach(row => {
        const nip = row.getAttribute('data-nip');
        const nama = row.getAttribute('data-nama');
        const tanggalCell = row.querySelector('td:first-child').textContent;
        const tanggalMatch = tanggalCell.match(/(\d{2})\s+(\w+)\s+(\d{4})/);
        
        let passSearch = nip.includes(searchTerm) || nama.includes(searchTerm);
        let passDate = true;
        
        if (tanggalMatch && (dateFrom || dateTo)) {
            const months = {'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04', 'May': '05', 'Jun': '06', 
                           'Jul': '07', 'Aug': '08', 'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'};
            const rowDate = `${tanggalMatch[3]}-${months[tanggalMatch[2]]}-${tanggalMatch[1]}`;
            
            if (dateFrom && rowDate < dateFrom) passDate = false;
            if (dateTo && rowDate > dateTo) passDate = false;
        }
        
        row.style.display = (passSearch && passDate) ? '' : 'none';
    });
}

document.getElementById('searchInput').addEventListener('keyup', filterTable);
document.getElementById('dateFrom').addEventListener('change', filterTable);
document.getElementById('dateTo').addEventListener('change', filterTable);
</script>
@endsection
