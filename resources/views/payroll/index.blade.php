@extends('layouts.app')
@section('content')
<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Payroll Harian</h1>
            <p class="text-xs text-slate-400 mt-1">Kelola payroll karyawan harian per periode</p>
        </div>
        <a href="{{ route('payroll.create') }}"
            class="bg-[#4F46E5] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#4338CA] transition">
            + Buat Payroll Baru
        </a>
    </div>

    @if($payrolls->isEmpty())
    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-12 text-center">
        <div class="text-4xl mb-3">💰</div>
        <p class="text-slate-500 text-sm">Belum ada payroll. Buat payroll pertama!</p>
        <a href="{{ route('payroll.create') }}"
            class="inline-block mt-4 bg-[#4F46E5] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#4338CA]">
            + Buat Payroll
        </a>
    </div>
    @else
    <div class="bg-white rounded-2xl border border-[#E5E7EB] overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-[#F8FAFC] border-b border-[#E5E7EB]">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Periode</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Karyawan</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Gaji</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payrolls as $p)
                <tr class="border-b border-[#E5E7EB]/50 hover:bg-[#F8FAFC]">
                    <td class="px-4 py-3 font-medium text-slate-800">{{ $p->periode }}</td>
                    <td class="px-4 py-3 text-slate-500 text-xs">
                        {{ \Carbon\Carbon::parse($p->tanggal_dari)->format('d M Y') }} —
                        {{ \Carbon\Carbon::parse($p->tanggal_sampai)->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $p->details->count() }} karyawan</td>
                    <td class="px-4 py-3 font-medium text-slate-800">
                        Rp {{ number_format($p->details->sum('total_gaji'), 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3">
                        @if($p->status === 'final')
                            <span class="text-xs bg-[#22C55E]/10 text-[#22C55E] px-2 py-0.5 rounded-full font-medium">Final</span>
                        @else
                            <span class="text-xs bg-[#F59E0B]/10 text-[#F59E0B] px-2 py-0.5 rounded-full font-medium">Draft</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('payroll.show', $p->id) }}"
                                class="text-xs px-2 py-1 rounded-lg border border-[#E5E7EB] text-slate-600 hover:bg-slate-50">
                                Lihat
                            </a>
                            @if($p->status === 'draft')
                            <form method="POST" action="{{ route('payroll.destroy', $p->id) }}"
                                onsubmit="return confirm('Hapus payroll ini?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="text-xs px-2 py-1 rounded-lg border border-red-200 text-red-500 hover:bg-red-50">
                                    Hapus
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
