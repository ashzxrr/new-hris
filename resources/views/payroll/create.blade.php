@extends('layouts.app')
@section('content')
<div class="max-w-lg">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-800">Buat Payroll Baru</h1>
        <p class="text-xs text-slate-400 mt-1">Pilih periode untuk generate payroll harian</p>
    </div>

    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6">
        <form method="POST" action="{{ route('payroll.store') }}">
            @csrf
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
                        📅 Periode 1 (1–13)
                    </button>
                    <button type="button" data-half="2" onclick="setPeriode('2')"
                        class="periode-btn text-sm px-3 py-2 rounded-lg border border-[#E5E7EB] text-slate-600 hover:bg-[#4F46E5]/5 hover:border-[#4F46E5]/30 transition text-left">
                        📅 Periode 2 (14–akhir)
                    </button>
                </div>
                <input type="hidden" name="tanggal_dari" id="tanggal_dari" required>
                <input type="hidden" name="tanggal_sampai" id="tanggal_sampai" required>
                <p id="periodeInfo" class="text-xs text-slate-400 mt-1">Pilih bulan dan periode untuk generate payroll.</p>
            </div>

            <div class="flex gap-3 justify-end">
                <a href="{{ route('payroll.index') }}"
                    class="pbtn pbtn-secondary">Batal</a>
                <button type="submit"
                    class="pbtn pbtn-primary">
                    <span class="pbtn-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8"/><path d="M7 3v5h8"/></svg>
                    </span>
                    <span>Buat Payroll</span>
                </button>
            </div>
        </form>
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
        sampai = `${year}-${month}-13`;
    } else {
        const lastDay = new Date(year, Number(month), 0).getDate();
        dari = `${year}-${month}-14`;
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
        periodeInfo.innerText = 'Pilih bulan dan periode untuk generate payroll.';
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
</script>
@endsection
