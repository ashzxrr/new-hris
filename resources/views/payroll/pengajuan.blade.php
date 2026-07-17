@extends('layouts.app')
@section('content')
<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Pengajuan Payroll — {{ $payroll->periode }}</h1>
            <p class="text-xs text-slate-400 mt-1">{{ \Carbon\Carbon::parse($payroll->tanggal_dari)->format('d M Y') }} — {{ \Carbon\Carbon::parse($payroll->tanggal_sampai)->format('d M Y') }}</p>
        </div>
        <a href="{{ route('payroll.show', $payroll->id) }}" class="pbtn pbtn-secondary">← Kembali</a>
    </div>

    @if($pengajuan->isEmpty())
        <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6 text-center">
            <p class="text-slate-500">Belum ada data pengajuan untuk periode ini.</p>
        </div>
    @else
        <div class="space-y-6">
            @foreach($pengajuan as $jenis => $rows)
                <div class="bg-white rounded-2xl border border-[#E5E7EB] p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">{{ $jenis }}</h2>
                            <p class="text-sm text-slate-500">{{ $rows->count() }} karyawan</p>
                        </div>
                        <div class="text-sm text-slate-500">Total: Rp {{ number_format($rows->sum('total_akhir'), 0, ',', '.') }}</div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border-separate border-spacing-y-2">
                            <thead class="text-xs uppercase tracking-wide text-slate-500 border-b border-[#E5E7EB]">
                                <tr>
                                    <th class="px-3 py-3 text-left">No</th>
                                    <th class="px-3 py-3 text-left">NIP</th>
                                    <th class="px-3 py-3 text-left">Nama</th>
                                    <th class="px-3 py-3 text-left">Jenis</th>
                                    <th class="px-3 py-3 text-right">Gaji Real</th>
                                    <th class="px-3 py-3 text-right">Komplain</th>
                                    <th class="px-3 py-3 text-right">Insentif</th>
                                    <th class="px-3 py-3 text-right">Potongan Lain</th>
                                    <th class="px-3 py-3 text-right">Potongan BPJS</th>
                                    <th class="px-3 py-3 text-right">Total Akhir</th>
                                    <th class="px-3 py-3 text-left">No Rekening</th>
                                    <th class="px-3 py-3 text-left">Bank</th>
                                    <th class="px-3 py-3 text-left">Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $index => $row)
                                    <tr class="border-b border-[#E5E7EB]/60 hover:bg-slate-50">
                                        <td class="px-3 py-3 text-left font-mono">{{ $index + 1 }}</td>
                                        <td class="px-3 py-3 text-left font-mono">{{ $row->nip }}</td>
                                        <td class="px-3 py-3 text-left">{{ $row->nama }}</td>
                                        <td class="px-3 py-3 text-left">{{ $row->jenis }}</td>
                                        <td class="px-3 py-3 text-right">Rp {{ number_format($row->gaji_real,0,',','.') }}</td>
                                        <td class="px-3 py-3 text-right">Rp {{ number_format($row->komplain,0,',','.') }}</td>
                                        <td class="px-3 py-3 text-right">Rp {{ number_format($row->insentif,0,',','.') }}</td>
                                        <td class="px-3 py-3 text-right">Rp {{ number_format($row->potongan_lain,0,',','.') }}</td>
                                        <td class="px-3 py-3 text-right">Rp {{ number_format($row->potongan_bpjs,0,',','.') }}</td>
                                        <td class="px-3 py-3 text-right font-semibold">Rp {{ number_format($row->total_akhir,0,',','.') }}</td>
                                        <td class="px-3 py-3 text-left">{{ $row->no_rekening ?? '-' }}</td>
                                        <td class="px-3 py-3 text-left">{{ $row->nama_bank ?? '-' }}</td>
                                        <td class="px-3 py-3 text-left">{{ $row->email ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
