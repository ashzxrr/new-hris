@extends('layouts.app')
@section('content')
<div class="max-w-lg">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-slate-800">Buat Payroll Baru</h1>
        <p class="text-xs text-slate-400 mt-1">Pilih periode untuk generate payroll harian</p>
    </div>

    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6">
        <form method="POST" action="{{ route('payroll.preview') }}">
            @csrf
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
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Tanggal Dari</label>
                    <input type="date" name="tanggal_dari" id="tanggal_dari" required
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                </div>
                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Tanggal Sampai</label>
                    <input type="date" name="tanggal_sampai" id="tanggal_sampai" required
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#4F46E5]/30">
                </div>
            </div>

            <div class="flex gap-3 justify-end">
                <a href="{{ route('payroll.index') }}"
                    class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm">Batal</a>
                <button type="submit"
                    class="bg-[#4F46E5] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#4338CA]">
                    Preview Payroll →
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

    if (half === '1') {
        document.getElementById('tanggal_dari').value = `${year}-${month}-01`;
        document.getElementById('tanggal_sampai').value = `${year}-${month}-15`;
    } else {
        const lastDay = new Date(year, now.getMonth() + 1, 0).getDate();
        document.getElementById('tanggal_dari').value = `${year}-${month}-16`;
        document.getElementById('tanggal_sampai').value = `${year}-${month}-${lastDay}`;
    }
}
</script>
@endsection
