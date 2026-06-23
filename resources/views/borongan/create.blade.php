@extends('layouts.app')
@section('content')
<div class="max-w-lg">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-800">Upload File Borongan</h1>
        <p class="text-xs text-slate-400 mt-1">Upload file Excel mentah dari admin cabut/cetak/moulding</p>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl p-3 mb-4">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6">
        <form method="POST" action="{{ route('borongan.upload') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="text-xs font-medium text-slate-500 mb-1 block">Jenis Borongan</label>
                <select name="jenis" required
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                    <option value="cetak">Cetak / HCR Indomie</option>
                    <option value="cabut">Cabut</option>
                    <option value="moulding" disabled>Moulding (coming soon)</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="text-xs font-medium text-slate-500 mb-1 block">Periode</label>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <button type="button" onclick="setPeriode('1')"
                        class="periode-btn text-sm px-3 py-2 rounded-lg border border-[#E5E7EB] text-slate-600 hover:bg-[#4F46E5]/5 hover:border-[#4F46E5]/30 transition text-left">
                        📅 Periode 1 (1–15)
                    </button>
                    <button type="button" onclick="setPeriode('2')"
                        class="periode-btn text-sm px-3 py-2 rounded-lg border border-[#E5E7EB] text-slate-600 hover:bg-[#4F46E5]/5 hover:border-[#4F46E5]/30 transition text-left">
                        📅 Periode 2 (16–akhir)
                    </button>
                </div>
                <input type="hidden" name="tanggal_dari" id="tanggal_dari" required>
                <input type="hidden" name="tanggal_sampai" id="tanggal_sampai" required>
                <p id="periodeInfo" class="text-xs text-slate-400 mt-1">Pilih periode untuk upload harian.</p>
            </div>

            <div class="mb-4">
                <label class="text-xs font-medium text-slate-500 mb-1 block">Tanggal</label>
                <input type="date" name="tanggal" id="tanggal" required value="{{ date('Y-m-d') }}"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                <p class="text-[10px] text-slate-400 mt-1">Upload data untuk tanggal ini. Pastikan tanggal berada dalam periode yang dipilih.</p>
            </div>

            <div class="mb-6">
                <label class="text-xs font-medium text-slate-500 mb-1 block">File Excel</label>
                <input type="file" name="file" accept=".xlsx,.xls" required
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-[#4F46E5]/10 file:text-[#4F46E5] file:text-xs">
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

<script>
function setPeriode(half) {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const dariInput = document.getElementById('tanggal_dari');
    const sampaiInput = document.getElementById('tanggal_sampai');
    const tanggalInput = document.getElementById('tanggal');
    let dari, sampai;

    if (half === '1') {
        dari = `${year}-${month}-01`;
        sampai = `${year}-${month}-15`;
    } else {
        const lastDay = new Date(year, now.getMonth() + 1, 0).getDate();
        dari = `${year}-${month}-16`;
        sampai = `${year}-${month}-${String(lastDay).padStart(2, '0')}`;
    }

    dariInput.value = dari;
    sampaiInput.value = sampai;
    document.getElementById('periodeInfo').innerText = `Periode terpilih: ${dari} s/d ${sampai}`;

    if (!tanggalInput.value || tanggalInput.value < dari || tanggalInput.value > sampai) {
        tanggalInput.value = dari;
    }
}

function initPeriode() {
    const now = new Date();
    setPeriode(now.getDate() <= 15 ? '1' : '2');
}

initPeriode();
</script>
@endsection
