@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-semibold text-slate-800">Riwayat PKWT</h1>
    <div class="flex items-center gap-2">
        <a href="{{ route('pkwt.index') }}" class="pbtn pbtn-ghost">
            <span class="pbtn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            </span>
            <span>Kembali</span>
        </a>
    </div>
</div>

{{-- Search --}}
<div class="mb-4">
    <form method="GET" action="{{ route('pkwt.riwayat') }}">
        <div class="flex items-center gap-2 max-w-md">
            <div class="relative flex-1">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari nama karyawan..."
                    autocomplete="off"
                    class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition"
                >
            </div>
            <button type="submit" class="pbtn pbtn-primary pbtn-sm">
                <span class="pbtn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <span>Cari</span>
            </button>
            @if(request('search'))
                <a href="{{ route('pkwt.riwayat') }}" class="pbtn pbtn-ghost pbtn-sm">Reset</a>
            @endif
        </div>
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl border border-[#E5E7EB] overflow-hidden">
    <div class="overflow-auto">
        <table class="w-full text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-[#F8FAFC] text-[11px] font-medium text-slate-400 uppercase tracking-wide">
                    <th class="px-4 py-3 text-left">Karyawan</th>
                    <th class="px-4 py-3 text-left">Nomor Surat</th>
                    <th class="px-4 py-3 text-left">Periode Kontrak</th>
                    <th class="px-4 py-3 text-center">Tanggal Export</th>
                    <th class="px-4 py-3 text-center">Dibuat Oleh</th>
                    <th class="px-4 py-3 text-center w-32">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($exports as $e)
                    <tr class="hover:bg-[#F8FAFC] transition-colors">
                        <td class="px-4 py-2.5">
                            <div class="font-medium text-slate-800">{{ $e->user->nama ?? '—' }}</div>
                            <div class="text-[11px] text-slate-400">{{ $e->user->nip ?? '' }}</div>
                        </td>
                        <td class="px-4 py-2.5 font-mono text-sm text-slate-700">{{ $e->nomor_surat }}</td>
                        <td class="px-4 py-2.5 text-slate-600">
                            {{ optional($e->tanggal_mulai)->isoFormat('D MMM Y') ?: '—' }}
                            &mdash;
                            {{ optional($e->tanggal_selesai)->isoFormat('D MMM Y') ?: '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-center text-slate-500">
                            {{ $e->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-4 py-2.5 text-center text-slate-500">
                            {{ $e->pembuat->name ?? '—' }}
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <a href="{{ route('pkwt.download', $e->id) }}"
                                class="pbtn pbtn-secondary pbtn-sm {{ !$e->file_path ? 'opacity-50 pointer-events-none' : '' }}">
                                <span class="pbtn-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                </span>
                                <span>Download</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">
                            Belum ada data PKWT.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
