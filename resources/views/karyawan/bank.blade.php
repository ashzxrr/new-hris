@extends('layouts.app')
@section('content')
<div class="max-w-4xl">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-800">Data Bank Karyawan</h1>
        <p class="text-xs text-slate-400 mt-1">Kelola nomor rekening dan data bank karyawan</p>
    </div>

    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
            <div class="flex-1 min-w-0">
                <input type="text" id="searchBank" placeholder="Cari NIP atau Nama..." value="{{ $searchQuery }}"
                    class="w-full border border-[#E5E7EB] rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
            </div>
            <div class="flex items-center gap-3">
                <select id="filterKategori" class="border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                    <option value="semua" {{ $kategoriFilter === 'semua' ? 'selected' : '' }}>Semua Kategori</option>
                    <option value="harian" {{ $kategoriFilter === 'harian' ? 'selected' : '' }}>Harian</option>
                    <option value="borongan" {{ $kategoriFilter === 'borongan' ? 'selected' : '' }}>Borongan</option>
                    <option value="bulanan" {{ $kategoriFilter === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                </select>
                <button type="button" onclick="applyBankFilters()" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Terapkan</button>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-[#E5E7EB] overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-[#F8FAFC] border-b border-[#E5E7EB]">
                    <tr class="text-xs text-slate-500 text-left uppercase tracking-wide">
                        <th class="px-4 py-3">NIP</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Kategori Gaji</th>
                        <th class="px-4 py-3">Bank</th>
                        <th class="px-4 py-3">No Rekening</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                    <tr class="border-b border-[#E5E7EB]/50 hover:bg-[#F8FAFC]">
                        <td class="px-4 py-3 font-mono">{{ $r->nip }}</td>
                        <td class="px-4 py-3">{{ $r->nama ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $kategori = $r->kategori_gaji ?? 'semua';
                                $badgeColor = 'bg-slate-100 text-slate-600';
                                if ($kategori === 'harian') $badgeColor = 'bg-[#ECFDF5] text-[#166534]';
                                if ($kategori === 'borongan') $badgeColor = 'bg-[#EEF2FF] text-[#4338CA]';
                                if ($kategori === 'bulanan') $badgeColor = 'bg-[#FFF7ED] text-[#C2410C]';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-medium {{ $badgeColor }}">
                                {{ ucfirst($kategori) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $r->nama_bank ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $r->no_rekening ?? 'Belum Lengkap' }}</td>
                        <td class="px-4 py-3">{{ $r->email ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <button type="button" onclick="openBankModal('{{ $r->nip }}', '{{ addslashes($r->nama ?? '') }}', '{{ addslashes($r->nama_bank ?? '') }}', '{{ addslashes($r->no_rekening ?? '') }}', '{{ addslashes($r->email ?? '') }}')" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-700">Edit</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td class="px-4 py-8 text-center text-sm text-slate-400" colspan="7">Belum ada data.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="bankModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-800" id="bankModalTitle">Edit Data Bank</h3>
            <button type="button" onclick="closeBankModal()" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <form id="bankForm">
            <input type="hidden" id="bank_nip" name="nip">
            <div class="mb-3">
                <label class="text-xs text-slate-500 mb-1 block">Nama Bank</label>
                <input type="text" id="bank_nama_bank" name="nama_bank" class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="mb-3">
                <label class="text-xs text-slate-500 mb-1 block">No Rekening</label>
                <input type="text" id="bank_no_rekening" name="no_rekening" class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="mb-3">
                <label class="text-xs text-slate-500 mb-1 block">Email</label>
                <input type="email" id="bank_email" name="email" class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm">
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeBankModal()" class="border border-[#E5E7EB] px-4 py-2 rounded-lg text-sm">Batal</button>
                <button type="button" onclick="submitBankForm()" class="bg-[#4F46E5] text-white px-4 py-2 rounded-lg text-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBankModal(nip, nama, bank, rekening, email) {
    document.getElementById('bankModalTitle').innerText = 'Edit Data Bank — ' + (nama || nip);
    document.getElementById('bank_nip').value = nip;
    document.getElementById('bank_nama_bank').value = bank || '';
    document.getElementById('bank_no_rekening').value = rekening || '';
    document.getElementById('bank_email').value = email || '';
    document.getElementById('bankModal').classList.remove('hidden');
}

function closeBankModal() {
    document.getElementById('bankModal').classList.add('hidden');
}

async function submitBankForm() {
    const nip = document.getElementById('bank_nip').value;
    const nama_bank = document.getElementById('bank_nama_bank').value;
    const no_rekening = document.getElementById('bank_no_rekening').value;
    const email = document.getElementById('bank_email').value;

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    const formData = new FormData();
    formData.append('nama_bank', nama_bank);
    formData.append('no_rekening', no_rekening);
    formData.append('email', email);

    const resp = await fetch('/karyawan/bank/' + encodeURIComponent(nip), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
        },
        body: formData
    });

    if (resp.ok) {
        location.reload();
    } else {
        alert('Gagal menyimpan.');
    }
}

function applyBankFilters() {
    const search = document.getElementById('searchBank').value;
    const kategori = document.getElementById('filterKategori').value;
    const params = new URLSearchParams();

    if (search) {
        params.set('search', search);
    }
    if (kategori && kategori !== 'semua') {
        params.set('kategori', kategori);
    }

    const queryString = params.toString();
    window.location.search = queryString ? `?${queryString}` : '';
}
</script>

@endsection
