@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-[#567C8D]">Payroll</p>
            <h1 class="text-2xl font-semibold text-[#2F4156]">Atur Gaji Harian</h1>
            <p class="mt-1 text-sm text-slate-500">Tetapkan tarif dan tanggal berlakunya per karyawan. Tarif baru disimpan sebagai riwayat.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-semibold">Periksa kembali data yang diisi.</p>
            <ul class="mt-1 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" action="{{ route('payroll.salary-config.index') }}" class="rounded-xl border border-[#D6E5EF] bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_16rem_16rem_auto] md:items-end">
            <div>
                <label for="search" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Cari karyawan</label>
                <input id="search" name="search" value="{{ $search }}" type="search" placeholder="Nama, NIP, atau PIN"
                    class="w-full rounded-lg border border-[#C8D9E6] px-3 py-2 text-sm text-[#2F4156] focus:border-[#567C8D] focus:outline-none focus:ring-2 focus:ring-[#567C8D]/20">
            </div>
            <div>
                <label for="bagian" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Bagian</label>
                <select id="bagian" name="bagian" class="w-full rounded-lg border border-[#C8D9E6] bg-white px-3 py-2 text-sm text-[#2F4156] focus:border-[#567C8D] focus:outline-none focus:ring-2 focus:ring-[#567C8D]/20">
                    <option value="">Semua Bagian</option>
                    @foreach ($bagianList as $namaBagian)
                        <option value="{{ $namaBagian }}" @selected($bagian === $namaBagian)>{{ $namaBagian }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sort" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Urutan karyawan</label>
                <select id="sort" name="sort" class="w-full rounded-lg border border-[#C8D9E6] bg-white px-3 py-2 text-sm text-[#2F4156] focus:border-[#567C8D] focus:outline-none focus:ring-2 focus:ring-[#567C8D]/20">
                    <option value="nama" @selected($sort === 'nama')>Nama A–Z</option>
                    <option value="terbaru" @selected($sort === 'terbaru')>Tanggal masuk terbaru</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-[#2F4156] px-4 py-2 text-sm font-medium text-white hover:bg-[#243447]">Terapkan</button>
                <a href="{{ route('payroll.salary-config.index') }}" class="rounded-lg border border-[#C8D9E6] px-4 py-2 text-sm font-medium text-[#567C8D] hover:bg-[#F8FAFC]">Reset</a>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('payroll.salary-config.store', request()->query()) }}" id="salaryConfigForm" class="overflow-hidden rounded-xl border border-[#D6E5EF] bg-white shadow-sm">
        @csrf
        <div class="flex flex-col gap-3 border-b border-[#E5EEF4] px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm font-semibold text-[#2F4156]">
                    <input type="checkbox" id="selectAllEmployees" class="rounded border-slate-300 text-[#2F4156] focus:ring-[#567C8D]">
                    Pilih semua di halaman ini
                </label>
                <span id="selectedCount" class="rounded-full bg-[#F1F5F9] px-2.5 py-1 text-xs text-slate-600">0 dipilih</span>
            </div>
            <button type="submit" id="saveSalaryConfigs" disabled
                class="rounded-lg bg-[#2F4156] px-4 py-2 text-sm font-semibold text-white transition enabled:hover:bg-[#243447] disabled:cursor-not-allowed disabled:opacity-50">
                Simpan tarif terpilih
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] text-left text-sm">
                <thead class="bg-[#F8FAFC] text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="w-12 px-4 py-3"></th>
                        <th class="px-4 py-3">Karyawan</th>
                        <th class="px-4 py-3">Bagian</th>
                        <th class="px-4 py-3">Tanggal masuk</th>
                        <th class="px-4 py-3">Tarif saat ini</th>
                        <th class="px-4 py-3">Tarif baru (Rp/hari)</th>
                        <th class="px-4 py-3">Berlaku dari</th>
                        <th class="px-4 py-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5EEF4]">
                    @php
                        $selectedEmployeeIds = old('employee_ids', []);
                        $selectedEmployeeIds = is_array($selectedEmployeeIds) ? array_map('strval', $selectedEmployeeIds) : [];
                    @endphp
                    @forelse ($employees as $employee)
                        @php
                            $canSetRate = filled($employee->pin) && filled($employee->nip);
                            $isSelected = in_array((string) $employee->id, $selectedEmployeeIds, true);
                            $currentRate = $employee->nip ? ($currentRates[$employee->nip] ?? null) : null;
                        @endphp
                        <tr class="salary-config-row transition hover:bg-[#F8FAFC]" data-selectable="{{ $canSetRate ? 'true' : 'false' }}">
                            <td class="px-4 py-3 align-top">
                                @if ($canSetRate)
                                    <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}" class="employee-checkbox mt-1 rounded border-slate-300 text-[#2F4156] focus:ring-[#567C8D]" @checked($isSelected)>
                                @else
                                    <input type="checkbox" disabled class="mt-1 rounded border-slate-300">
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="font-medium text-[#2F4156]">{{ $employee->nama }}</div>
                                <div class="mt-1 text-xs text-slate-500">NIP: {{ $employee->nip ?: 'Belum ada' }} · PIN: {{ $employee->pin ?: 'Belum ada' }}</div>
                                @unless ($canSetRate)
                                    <p class="mt-1 text-xs text-amber-700">Lengkapi PIN dan NIP agar tarif dapat digunakan payroll.</p>
                                @endunless
                            </td>
                            <td class="px-4 py-3 align-top text-slate-600">{{ $employee->bagian ?: '—' }}</td>
                            <td class="px-4 py-3 align-top text-slate-600">
                                {{ $employee->tanggal_masuk?->format('d/m/Y') ?? 'Belum tercatat' }}
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if ($currentRate)
                                    <div class="font-medium text-[#2F4156]">Rp {{ number_format($currentRate->nominal, 0, ',', '.') }}</div>
                                    <div class="mt-1 text-xs text-slate-500">Sejak {{ $currentRate->berlaku_dari->format('d/m/Y') }}</div>
                                @else
                                    <span class="text-slate-400">Belum diatur</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top">
                                <input type="number" min="1" step="1" name="entries[{{ $employee->id }}][nominal]"
                                    value="{{ old("entries.{$employee->id}.nominal") }}" placeholder="Contoh: 150000"
                                    class="salary-entry-field w-40 rounded-lg border border-[#C8D9E6] px-3 py-2 text-sm disabled:bg-slate-50"
                                    @disabled(! $canSetRate || ! $isSelected)>
                                @error("entries.{$employee->id}.nominal")
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-4 py-3 align-top">
                                <input type="date" name="entries[{{ $employee->id }}][berlaku_dari]"
                                    value="{{ old("entries.{$employee->id}.berlaku_dari", now()->toDateString()) }}"
                                    class="salary-entry-field w-40 rounded-lg border border-[#C8D9E6] px-3 py-2 text-sm disabled:bg-slate-50"
                                    @disabled(! $canSetRate || ! $isSelected)>
                                @error("entries.{$employee->id}.berlaku_dari")
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-4 py-3 align-top">
                                <input type="text" maxlength="255" name="entries[{{ $employee->id }}][keterangan]"
                                    value="{{ old("entries.{$employee->id}.keterangan") }}" placeholder="Opsional"
                                    class="salary-entry-field w-48 rounded-lg border border-[#C8D9E6] px-3 py-2 text-sm disabled:bg-slate-50"
                                    @disabled(! $canSetRate || ! $isSelected)>
                                @error("entries.{$employee->id}.keterangan")
                                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                                @enderror
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-500">Tidak ada karyawan harian yang sesuai dengan pencarian atau filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($employees->hasPages())
            <div class="border-t border-[#E5EEF4] px-4 py-3">
                {{ $employees->links() }}
            </div>
        @endif
    </form>
</div>
@endsection

@push('scripts')
    @vite('resources/js/salary-config.js')
@endpush
