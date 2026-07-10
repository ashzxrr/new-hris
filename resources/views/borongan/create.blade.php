@extends('layouts.app')
@section('content')
<div class="max-w-lg">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-800">Upload File Borongan</h1>
        <p class="text-xs text-slate-400 mt-1">Upload file Excel mentah dari admin cabut/HCR/moulding</p>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl p-3 mb-4">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6">
        <form id="uploadBoronganForm" method="POST" action="{{ route('borongan.upload') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="payroll_id" value="{{ request('payroll_id') }}">
            <div class="mb-4">
                <label class="text-xs font-medium text-slate-500 mb-1 block">Jenis Borongan</label>
                <select name="jenis" required
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                    <option value="hcr" {{ request('jenis') === 'hcr' ? 'selected' : '' }}>HCR</option>
                    <option value="cabut" {{ request('jenis') === 'cabut' ? 'selected' : '' }}>Cabut</option>
                    <option value="moulding" {{ request('jenis') === 'moulding' ? 'selected' : '' }}>Moulding/Cetak</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="text-xs font-medium text-slate-500 mb-1 block">Bulan</label>
                <input type="month" name="bulan" id="bulan" required
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
            </div>
            <div class="mb-4">
                <label class="text-xs font-medium text-slate-500 mb-1 block">Periode</label>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <button type="button" data-half="1" onclick="setPeriode('1')"
                        class="periode-btn text-sm px-3 py-2 rounded-lg border border-[#E5E7EB] text-slate-600 hover:bg-[#4F46E5]/5 hover:border-[#4F46E5]/30 transition text-left">
                        📅 Periode 1 (1–15)
                    </button>
                    <button type="button" data-half="2" onclick="setPeriode('2')"
                        class="periode-btn text-sm px-3 py-2 rounded-lg border border-[#E5E7EB] text-slate-600 hover:bg-[#4F46E5]/5 hover:border-[#4F46E5]/30 transition text-left">
                        📅 Periode 2 (16–akhir)
                    </button>
                </div>
                <input type="hidden" name="tanggal_dari" id="tanggal_dari" required>
                <input type="hidden" name="tanggal_sampai" id="tanggal_sampai" required>
                <p id="periodeInfo" class="text-xs text-slate-400 mt-1">Pilih bulan dan periode untuk upload harian.</p>
            </div>

            

            <div class="mb-6">
                <label class="text-xs font-medium text-slate-500 mb-1 block">File Excel</label>
                
                <!-- Single file input for cetak/cabut -->
                <div id="fileInputSingle">
                    <input type="file" name="file" accept=".xlsx,.xls"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-[#4F46E5]/10 file:text-[#4F46E5] file:text-xs">
                    <p class="text-[10px] text-slate-400 mt-1">File boleh berisi banyak sheet, satu sheet = satu tanggal (nama sheet harus berupa angka tanggal, contoh: "16", "17", "18").</p>
                </div>
                
                <!-- Single file input for moulding -->
                <div id="fileInputMoulding" class="hidden">
                    <div>
                        <label class="text-xs text-slate-500 mb-1 block">File Excel</label>
                        <input type="file" name="file_kategori" accept=".xlsx,.xls" required
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-[#4F46E5]/10 file:text-[#4F46E5] file:text-xs">
                        <p class="text-[11px] text-slate-400 mt-1">File boleh berisi banyak sheet, satu sheet = satu tanggal (nama sheet harus berupa angka tanggal, contoh: "16", "17", "18").</p>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="debug" value="1" 
                        class="rounded border-[#E5E7EB] text-[#4F46E5] focus:ring-[#4F46E5]/30">
                    <span class="text-xs text-slate-500">🐛 Debug: Lihat kolom yang terdeteksi (jangan submit jika di-check)</span>
                </label>
            </div>

            <div class="flex gap-3 justify-end">
                <a href="{{ route('borongan.index') }}"
                    class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm">Batal</a>
                <button type="submit"
                    class="bg-[#4F46E5] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#4338CA]">
                    📤 Upload & Parse
                </button>
            </div>
        </form>
    </div>
</div>

<div id="duplikatModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 max-h-[80vh] overflow-y-auto">
        <div class="p-6">
            <h3 class="font-semibold text-slate-800 mb-1">⚠️ Tanggal Sudah Ada Data</h3>
            <p class="text-sm text-slate-600 mb-4">Beberapa tanggal di file ini sudah punya data sebelumnya:</p>
            <div id="duplikatList" class="space-y-2 mb-4 text-sm"></div>
            <p class="text-xs text-slate-400 mb-4">Tanggal berstatus APPROVED akan dilewati (tidak diubah). Tanggal lain akan direvisi dan diganti dengan data baru. Pilih "Lanjutkan" untuk proses, atau "Batalkan".</p>
            <div class="flex gap-3">
                <button onclick="document.getElementById('duplikatModal').classList.add('hidden')" class="flex-1 border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm">
                    Batalkan
                </button>
                <button id="btnLanjutkanRevisi" class="flex-1 bg-amber-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-600">
                    Lanjutkan sebagai Revisi
                </button>
            </div>
        </div>
    </div>
</div>

<div id="uploadLoadingOverlay" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-[60] flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl p-8 flex flex-col items-center gap-4 max-w-sm mx-4">
        <svg class="animate-spin h-10 w-10 text-[#4F46E5]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <p id="uploadLoadingText" class="text-sm font-medium text-slate-700 text-center">Mengupload dan memproses file...</p>
        <p class="text-xs text-slate-400 text-center">Mohon tunggu, jangan tutup atau refresh halaman ini.</p>
    </div>
</div>

<script>
function setPeriode(half) {
    const bulanInput = document.getElementById('bulan');
    const periodeInfo = document.getElementById('periodeInfo');
    const dariInput = document.getElementById('tanggal_dari');
    const sampaiInput = document.getElementById('tanggal_sampai');
    const now = new Date();
    const bulanValue = bulanInput.value || `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;

    if (!bulanValue) {
        periodeInfo.innerText = 'Pilih bulan terlebih dahulu.';
        return;
    }

    const [year, month] = bulanValue.split('-');
    let dari, sampai;

    if (half === '1') {
        dari = `${year}-${month}-01`;
        sampai = `${year}-${month}-15`;
    } else {
        const lastDay = new Date(year, Number(month), 0).getDate();
        dari = `${year}-${month}-16`;
        sampai = `${year}-${month}-${String(lastDay).padStart(2, '0')}`;
    }

    dariInput.value = dari;
    sampaiInput.value = sampai;
    periodeInfo.innerText = `Periode terpilih: ${dari} s/d ${sampai}`;
}

function updatePeriodeInfo() {
    const periodeInfo = document.getElementById('periodeInfo');
    const bulanInput = document.getElementById('bulan');
    const bulanValue = bulanInput.value;

    if (!bulanValue) {
        periodeInfo.innerText = 'Pilih bulan dan periode untuk upload harian.';
        return;
    }

    const selectedHalf = document.querySelector('.periode-btn.selected');
    if (selectedHalf) {
        setPeriode(selectedHalf.dataset.half);
    }
}

const periodeButtons = document.querySelectorAll('.periode-btn');
periodeButtons.forEach(btn => {
    btn.addEventListener('click', function() {
        periodeButtons.forEach(b => b.classList.remove('selected', 'bg-[#4F46E5]/10', 'border-[#4F46E5]'));
        this.classList.add('selected', 'bg-[#4F46E5]/10', 'border-[#4F46E5]');
    });
});

const bulanInput = document.getElementById('bulan');
bulanInput.addEventListener('change', updatePeriodeInfo);

window.addEventListener('DOMContentLoaded', function() {
    if (!bulanInput.value) {
        const now = new Date();
        bulanInput.value = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`;
    }
    updatePeriodeInfo();
});

// Toggle file inputs based on jenis selection
function toggleFileInputs() {
    const jenis = document.querySelector('select[name="jenis"]').value;
    const fileInputSingle = document.getElementById('fileInputSingle');
    const fileInputMoulding = document.getElementById('fileInputMoulding');
    const singleFileInput = fileInputSingle.querySelector('input[name="file"]');
    const mouldingKategoriInput = fileInputMoulding.querySelector('input[name="file_kategori"]');
    
    if (jenis === 'moulding') {
        fileInputSingle.classList.add('hidden');
        fileInputMoulding.classList.remove('hidden');
        singleFileInput.removeAttribute('required');
        mouldingKategoriInput.setAttribute('required', 'required');
    } else {
        fileInputSingle.classList.remove('hidden');
        fileInputMoulding.classList.add('hidden');
        singleFileInput.setAttribute('required', 'required');
        mouldingKategoriInput.removeAttribute('required');
    }
}

function initializeForm() {
    console.log('initializeForm() called');
    // Call on page load to set initial state
    toggleFileInputs();

    // Call on jenis dropdown change
    const jenisSelect = document.querySelector('select[name="jenis"]');
    jenisSelect?.addEventListener('change', toggleFileInputs);

    const form = document.getElementById('uploadBoronganForm');
    if (!form) return;
    console.log('Form found:', form);

    form.addEventListener('submit', function(e) {
        console.log('Submit event fired, target:', e.target);
        e.preventDefault();
        console.log('preventDefault called, defaultPrevented:', e.defaultPrevented);
        const jenis = document.querySelector('select[name="jenis"]').value;
        const jenisLabel = { 'hcr': 'HCR', 'cabut': 'CABUT', 'moulding': 'MOULDING' };
        const confirm_msg = `⚠️ PERHATIAN!\n\nYakin mau upload jenis: ${jenisLabel[jenis]}?\n\nData ini TERPISAH dari jenis lainnya dan tidak bisa dicampur.\n\nJika salah, gunakan Undo Upload di halaman review.`;
        if (!confirm(confirm_msg)) return;
        submitUploadForm(this, false);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeForm);
} else {
    initializeForm();
}

// Confirm sebelum submit - pastikan user tidak salah pilih jenis
function submitUploadForm(form, confirmRevisi) {
    console.log('submitUploadForm called with confirmRevisi:', confirmRevisi, 'form action:', form.action);
    const overlay = document.getElementById('uploadLoadingOverlay');
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    document.getElementById('uploadLoadingText').textContent = confirmRevisi
        ? 'Menghapus data lama dan memproses revisi...'
        : 'Mengupload dan memproses file...';

    const formData = new FormData(form);
    if (confirmRevisi) {
        formData.set('confirm_revisi', 'true');
    }

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(async response => {
        console.log('Response received, status:', response.status, 'redirected:', response.redirected, 'url:', response.url);
        if (response.status === 409) {
            console.log('Entering 409 branch');
            hideUploadLoading();
            const data = await response.json();
            showDuplikatModal(data.duplikat, form);
            return;
        }
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }
        if (!response.ok) {
            const text = await response.text();
            hideUploadLoading();
            document.open();
            document.write(text);
            document.close();
            return;
        }
        window.location.href = "{{ route('borongan.index') }}";
    })
    .catch(e => {
        hideUploadLoading();
        alert('Gagal upload: ' + e.message);
    });
}

function hideUploadLoading() {
    const overlay = document.getElementById('uploadLoadingOverlay');
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
}

function showDuplikatModal(duplikatList, form) {
    const container = document.getElementById('duplikatList');
    container.innerHTML = '';
    duplikatList.forEach(d => {
        const statusBadge = d.import_lama.status === 'approved'
            ? '<span class="text-red-600 font-medium">APPROVED - akan diskip dan tidak diubah</span>'
            : `<span class="text-amber-600">${d.import_lama.status}</span>`;
        container.innerHTML += `
        <div class="border border-[#E5E7EB] rounded-lg p-3">
            <div class="font-medium text-slate-700">${d.tanggal}</div>
            <div class="text-xs text-slate-500">File lama: ${d.import_lama.filename}</div>
            <div class="text-xs mt-1">Status: ${statusBadge}</div>
        </div>`;
    });
    
    const adaApproved = duplikatList.some(d => d.import_lama.status === 'approved');
    const btnLanjut = document.getElementById('btnLanjutkanRevisi');
    btnLanjut.disabled = false; // tetap bisa lanjut, tanggal approved otomatis di-skip di backend
    btnLanjut.className = 'flex-1 bg-amber-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-amber-600';
    btnLanjut.title = '';
    btnLanjut.onclick = function() {
        document.getElementById('duplikatModal').classList.add('hidden');
        submitUploadForm(form, true);
    };
    
    document.getElementById('duplikatModal').classList.remove('hidden');
}
</script>
@endsection
