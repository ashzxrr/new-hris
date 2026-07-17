@extends('layouts.app')
@section('content')
<div class="w-full">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-800">Data Bank Karyawan</h1>
        <p class="text-xs text-slate-400 mt-1">Kelola nomor rekening dan data bank karyawan</p>
    </div>

    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
            <div class="flex-1 min-w-0">
                <input type="text" id="searchBank" placeholder="Cari NIP atau Nama..." value="{{ $searchQuery }}" oninput="debouncedBankSearch()"
                    class="w-full border border-[#E5E7EB] rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="openInsertBankModal()" class="pbtn pbtn-primary pbtn-sm">Tambah Data Bank</button>
            </div>
        </div>

        <div class="overflow-auto max-h-[70vh] border border-[#E5E7EB] rounded-2xl bg-white">
            <table class="w-full text-sm whitespace-nowrap min-w-[900px]">
                <thead class="bg-[#F8FAFC] text-[11px] font-semibold text-slate-500 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">NIP</th>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Kategori</th>
                        <th class="px-4 py-3 text-left">Bank</th>
                        <th class="px-4 py-3 text-left">No Rekening</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse($rows as $r)
                    <tr class="hover:bg-[#F9FBFD] transition-colors duration-150">
                        <td class="px-4 py-3 font-mono text-slate-500 whitespace-nowrap truncate">{{ $r->nip }}</td>
                        <td class="px-4 py-3 text-slate-700 truncate" title="{{ $r->nama }}">{{ $r->nama ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @php
                                $kategori = $r->kategori_gaji ?? 'semua';
                                $badgeColor = 'bg-[#EAF1F6] text-[#2F4156]';
                                if ($kategori === 'harian') $badgeColor = 'bg-[#E0F2EA] text-[#1B7A4A]';
                                if ($kategori === 'borongan') $badgeColor = 'bg-[#EAF1F6] text-[#2F4156]';
                                if ($kategori === 'bulanan') $badgeColor = 'bg-[#FFF3DC] text-[#9A6200]';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-medium whitespace-nowrap {{ $badgeColor }}">
                                {{ ucfirst($kategori) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-700 truncate" title="{{ $r->nama_bank }}">{{ $r->nama_bank ?? '-' }}</td>
                        <td class="px-4 py-3 font-mono text-slate-500 whitespace-nowrap">
                            @if($r->no_rekening)
                                {{ $r->no_rekening }}
                            @else
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-medium bg-[#FDECEC] text-[#B91C1C]">Belum Lengkap</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-700 truncate" title="{{ $r->email }}">{{ $r->email ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" onclick="openBankModal('{{ $r->nip }}', '{{ addslashes($r->nama ?? '') }}', '{{ addslashes($r->nama_bank ?? '') }}', '{{ addslashes($r->no_rekening ?? '') }}', '{{ addslashes($r->email ?? '') }}')" class="pbtn pbtn-primary pbtn-sm">Edit</button>
                                <button type="button" onclick="deleteBankData('{{ $r->nip }}', '{{ addslashes($r->nama ?? '') }}')" class="pbtn pbtn-danger pbtn-sm">Hapus</button>
                            </div>
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

<div id="insertBankModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-800">Tambah Data Bank Karyawan</h3>
            <button type="button" onclick="closeInsertBankModal()" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <div class="mb-4">
            <label class="text-xs text-slate-500 mb-1 block">Cari Karyawan (NIP atau Nama)</label>
            <div class="flex gap-2">
                <input type="text" id="insertBankUserInput" list="insertBankUserList" placeholder="Ketik NIP atau nama..."
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm" autocomplete="off">
                <button type="button" onclick="addInsertBankUser()" class="pbtn pbtn-primary pbtn-sm">Tambah</button>
            </div>
            <datalist id="insertBankUserList">
                @foreach($eligibleUsers as $user)
                    <option value="{{ $user->nip }}" label="{{ $user->nama }}"></option>
                @endforeach
            </datalist>
            <p class="mt-2 text-[12px] text-slate-400">Pilih karyawan dari daftar untuk memasukkan data bank baru.</p>
        </div>

        <div class="mb-4">
            <div class="text-xs font-semibold text-slate-500 uppercase mb-2">Karyawan Terpilih</div>
            <div id="insertBankSelectedList" class="space-y-2 text-sm text-slate-700">
                <div class="text-slate-400">Belum ada karyawan yang dipilih.</div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <button type="button" onclick="closeInsertBankModal()" class="pbtn pbtn-secondary">Batal</button>
            <button type="button" onclick="submitInsertBankForm()" class="pbtn pbtn-primary">Simpan</button>
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
                <button type="button" onclick="closeBankModal()" class="pbtn pbtn-secondary">Batal</button>
                <button type="button" onclick="submitBankForm()" class="pbtn pbtn-primary">
                    <span class="pbtn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    </span>
                    <span>Simpan</span>
                </button>
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

const bankBaseUrl = @json(url('/karyawan/bank'));

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

    const resp = await fetch(`${bankBaseUrl}/${encodeURIComponent(nip)}`, {
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

const eligibleUsers = @json($eligibleUsers->keyBy('nip'));
let selectedInsertBankNips = [];

async function deleteBankData(nip, nama) {
    if (!confirm('Hapus data bank untuk ' + (nama || nip) + '?')) return;

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const resp = await fetch(`${bankBaseUrl}/${encodeURIComponent(nip)}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': token }
    });

    if (resp.ok) {
        location.reload();
    } else {
        alert('Gagal menghapus.');
    }
}

function openInsertBankModal() {
    selectedInsertBankNips = [];
    document.getElementById('insertBankUserInput').value = '';
    updateInsertBankSelectedList();
    document.getElementById('insertBankModal').classList.remove('hidden');
}

function closeInsertBankModal() {
    document.getElementById('insertBankModal').classList.add('hidden');
}

function addInsertBankUser() {
    const input = document.getElementById('insertBankUserInput');
    const nip = input.value.trim();

    if (!nip) {
        alert('Silakan pilih karyawan terlebih dahulu.');
        return;
    }

    if (!eligibleUsers[nip]) {
        alert('Karyawan tidak ditemukan atau sudah memiliki data bank.');
        return;
    }

    if (selectedInsertBankNips.includes(nip)) {
        alert('Karyawan sudah ditambahkan.');
        input.value = '';
        return;
    }

    selectedInsertBankNips.push(nip);
    input.value = '';
    updateInsertBankSelectedList();
}

function removeInsertBankUser(nip) {
    selectedInsertBankNips = selectedInsertBankNips.filter(item => item !== nip);
    updateInsertBankSelectedList();
}

function updateInsertBankSelectedList() {
    const list = document.getElementById('insertBankSelectedList');

    if (selectedInsertBankNips.length === 0) {
        list.innerHTML = '<div class="text-slate-400">Belum ada karyawan yang dipilih.</div>';
        return;
    }

    list.innerHTML = selectedInsertBankNips.map(nip => {
        const nama = eligibleUsers[nip]?.nama || '-';
        const kategori = eligibleUsers[nip]?.kategori_gaji || '';
        return `
            <div class="rounded-lg border border-[#E5E7EB] p-3 bg-[#F8FAFC] mb-2" data-nip="${nip}">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-sm font-medium text-slate-700">${nip} — ${nama}</div>
                    <button type="button" onclick="removeInsertBankUser('${nip}')" class="text-xs text-[#B91C1C] hover:text-[#7F1D1D]">Hapus</button>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <select class="insert-kategori border border-[#E5E7EB] rounded-lg px-2 py-1.5 text-xs" data-field="kategori_gaji">
                        <option value="" ${!kategori ? 'selected' : ''}>Kategori Gaji</option>
                        <option value="harian" ${kategori === 'harian' ? 'selected' : ''}>Harian</option>
                        <option value="borongan" ${kategori === 'borongan' ? 'selected' : ''}>Borongan</option>
                        <option value="bulanan" ${kategori === 'bulanan' ? 'selected' : ''}>Bulanan</option>
                    </select>
                    <input type="text" class="insert-bank border border-[#E5E7EB] rounded-lg px-2 py-1.5 text-xs" data-field="nama_bank" placeholder="Nama Bank">
                    <input type="text" class="insert-rekening border border-[#E5E7EB] rounded-lg px-2 py-1.5 text-xs" data-field="no_rekening" placeholder="No Rekening">
                    <input type="email" class="insert-email border border-[#E5E7EB] rounded-lg px-2 py-1.5 text-xs" data-field="email" placeholder="Email">
                </div>
            </div>
        `;
    }).join('');
}

async function submitInsertBankForm() {
    if (selectedInsertBankNips.length === 0) {
        alert('Pilih setidaknya satu karyawan untuk dimasukkan.');
        return;
    }

    const items = selectedInsertBankNips.map(nip => {
        const card = document.querySelector(`#insertBankSelectedList [data-nip="${nip}"]`);
        return {
            nip,
            kategori_gaji: card.querySelector('.insert-kategori').value,
            nama_bank: card.querySelector('.insert-bank').value,
            no_rekening: card.querySelector('.insert-rekening').value,
            email: card.querySelector('.insert-email').value,
        };
    });

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const resp = await fetch(bankBaseUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ items }),
    });

    if (resp.ok) {
        location.reload();
    } else {
        alert('Gagal menyimpan data bank.');
    }
}

let bankSearchTimer = null;
function debouncedBankSearch() {
    clearTimeout(bankSearchTimer);
    bankSearchTimer = setTimeout(() => {
        const search = document.getElementById('searchBank').value;
        const params = new URLSearchParams(window.location.search);
        if (search) {
            params.set('search', search);
        } else {
            params.delete('search');
        }
        window.location.search = params.toString();
    }, 500);
}
</script>

@endsection
