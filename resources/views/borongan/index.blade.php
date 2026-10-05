@extends('layouts.app')
@section('content')
<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Import Borongan</h1>
            <p class="text-xs text-slate-400 mt-1">Upload dan kelola data payroll borongan (Cabut, HCR, Moulding/Cetak)</p>
        </div>
        <a href="{{ route('borongan.create') }}"
            class="pbtn pbtn-primary">
            + Upload File Baru
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-xl p-3 mb-4">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl p-3 mb-4">
        {{ session('error') }}
    </div>
    @endif

    @if($imports->isEmpty())
    <div class="bg-white rounded-2xl border border-[#E5E7EB] p-12 text-center">
        <div class="text-4xl mb-3">📦</div>
        <p class="text-slate-500 text-sm">Belum ada data import. Upload file pertama!</p>
        <a href="{{ route('borongan.create') }}"
            class="inline-block mt-4 pbtn pbtn-primary">
            + Upload File
        </a>
    </div>
    @else
    @php
        $jenisLabels = [
            'cetak' => 'HCR',
            'moulding' => 'Moulding/Cetak',
            'cabut' => 'Cabut',
            'nkk' => 'NKK',
        ];
    @endphp
    <div class="bg-white rounded-2xl border border-[#E5E7EB] overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-[#F8FAFC] border-b border-[#E5E7EB]">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Jenis</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Filename</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Periode</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Total Baris</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Flagged</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-400 uppercase tracking-wide">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($imports as $imp)
                <tr class="border-b border-[#E5E7EB]/50 hover:bg-[#F8FAFC]">
                    <td class="px-4 py-3">
                        <span class="text-xs bg-[#4F46E5]/10 text-[#4F46E5] px-2 py-0.5 rounded-full uppercase font-medium">
                            {{ $jenisLabels[$imp->jenis] ?? ucfirst($imp->jenis) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $imp->filename }}</td>
                    <td class="px-4 py-3 text-slate-500 text-xs">
                        {{ \Carbon\Carbon::parse($imp->tanggal_dari)->format('d M') }} —
                        {{ \Carbon\Carbon::parse($imp->tanggal_sampai)->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3 text-slate-700">{{ $imp->total_baris }}</td>
                    <td class="px-4 py-3">
                        @if($imp->total_flagged > 0)
                            <span class="text-xs bg-[#F59E0B]/10 text-[#F59E0B] px-2 py-0.5 rounded-full font-medium">
                                {{ $imp->total_flagged }} flagged
                            </span>
                        @else
                            <span class="text-xs text-slate-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($imp->status === 'approved')
                            <span class="text-xs bg-[#22C55E]/10 text-[#22C55E] px-2 py-0.5 rounded-full font-medium">Approved</span>
                        @elseif($imp->status === 'reviewed')
                            <span class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full font-medium">Reviewed</span>
                        @else
                            <span class="text-xs bg-[#F59E0B]/10 text-[#F59E0B] px-2 py-0.5 rounded-full font-medium">Pending</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('borongan.review', $imp->id) }}"
                                class="text-xs px-2 py-1 rounded-lg border border-[#E5E7EB] text-slate-600 hover:bg-slate-50">
                                Review
                            </a>
                            <a href="{{ route('borongan.rekapIndex', $imp->id) }}"
                                class="text-xs px-2 py-1 rounded-lg border border-[#E5E7EB] text-slate-600 hover:bg-slate-50">
                                Rekap
                            </a>
                            @if($imp->status !== 'approved')
                            <form method="POST" action="{{ route('borongan.destroy', $imp->id) }}"
                                onsubmit="return confirm('Hapus import ini?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="pbtn pbtn-danger pbtn-sm">
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
