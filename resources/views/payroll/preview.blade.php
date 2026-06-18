@extends('layouts.app')
@section('content')
<div>
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Preview Payroll</h1>
            <p class="text-xs text-slate-400 mt-1">
                Periode: {{ \Carbon\Carbon::parse($dari)->format('d M Y') }} —
                {{ \Carbon\Carbon::parse($sampai)->format('d M Y') }}
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('payroll.create') }}"
                class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm">← Kembali</a>
            <form method="POST" action="{{ route('payroll.store') }}">
                @csrf
                <input type="hidden" name="tanggal_dari" value="{{ $dari }}">
                <input type="hidden" name="tanggal_sampai" value="{{ $sampai }}">
                <button type="submit"
                    class="bg-[#4F46E5] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#4338CA]"
                    onclick="return confirm('Generate dan simpan payroll ini?')">
                    💾 Generate Payroll
                </button>
            </form>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-4 gap-4 mb-5">
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Karyawan</div>
            <div class="text-2xl font-bold text-slate-800">{{ count($previewData) }}</div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Gaji Pokok</div>
            <div class="text-xl font-bold text-slate-800">
                Rp {{ number_format(collect($previewData)->sum('gaji_pokok'), 0, ',', '.') }}
            </div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Lembur</div>
            <div class="text-xl font-bold text-amber-500">
                Rp {{ number_format(collect($previewData)->sum('gaji_lembur'), 0, ',', '.') }}
            </div>
        </div>
        <div class="bg-white rounded-xl border border-[#E5E7EB] p-4">
            <div class="text-xs text-slate-400 mb-1">Total Keseluruhan</div>
            <div class="text-xl font-bold text-[#4F46E5]">
                Rp {{ number_format(collect($previewData)->sum('total_gaji'), 0, ',', '.') }}
            </div>
        </div>
    </div>

    {{-- Tabel preview --}}
    <div class="bg-white rounded-2xl border border-[#E5E7EB] overflow-x-auto">
        <table class="w-full text-xs">
            <thead class="bg-[#F8FAFC] border-b border-[#E5E7EB]">
                <tr>
                    <th class="px-3 py-2.5 text-left font-semibold text-slate-400 uppercase tracking-wide">Nama</th>
                    <th class="px-3 py-2.5 text-left font-semibold text-slate-400 uppercase tracking-wide">NIP</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Nominal</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Hadir</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Alpha</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Izin</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Sakit</th>
                    <th class="px-3 py-2.5 text-center font-semibold text-slate-400 uppercase tracking-wide">Lembur</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Gaji Pokok</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Lembur</th>
                    <th class="px-3 py-2.5 text-right font-semibold text-slate-400 uppercase tracking-wide">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($previewData as $d)
                <tr class="border-b border-[#E5E7EB]/50 hover:bg-[#F8FAFC]">
                    <td class="px-3 py-2.5 font-medium text-slate-800">{{ $d['nama'] }}</td>
                    <td class="px-3 py-2.5 text-slate-400 font-mono">{{ $d['nip'] }}</td>
                    <td class="px-3 py-2.5 text-center text-slate-600">Rp {{ number_format($d['nominal'], 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-center font-medium text-green-600">{{ $d['hadir'] }}</td>
                    <td class="px-3 py-2.5 text-center text-red-500">{{ $d['alpha'] }}</td>
                    <td class="px-3 py-2.5 text-center text-amber-500">{{ $d['izin'] }}</td>
                    <td class="px-3 py-2.5 text-center text-blue-500">{{ $d['sakit'] }}</td>
                    <td class="px-3 py-2.5 text-center text-purple-500">{{ $d['lembur_menit'] }} mnt</td>
                    <td class="px-3 py-2.5 text-right text-slate-700">Rp {{ number_format($d['gaji_pokok'], 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right text-amber-600">Rp {{ number_format($d['gaji_lembur'], 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-[#4F46E5]">Rp {{ number_format($d['total_gaji'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-[#F8FAFC] border-t-2 border-[#E5E7EB]">
                <tr>
                    <td colspan="8" class="px-3 py-2.5 font-semibold text-slate-700 text-right">TOTAL</td>
                    <td class="px-3 py-2.5 text-right font-bold text-slate-800">Rp {{ number_format(collect($previewData)->sum('gaji_pokok'), 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-amber-600">Rp {{ number_format(collect($previewData)->sum('gaji_lembur'), 0, ',', '.') }}</td>
                    <td class="px-3 py-2.5 text-right font-bold text-[#4F46E5]">Rp {{ number_format(collect($previewData)->sum('total_gaji'), 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
