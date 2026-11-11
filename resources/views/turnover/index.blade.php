@extends('layouts.app')

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-[#2F4156]">Turnover Karyawan</h1>
            <p class="mt-1 text-sm text-[#567C8D]">Pantau karyawan masuk dan resign berdasarkan data HRIS.</p>
        </div>

        <form method="GET" action="{{ route('turnover.index') }}" class="flex items-end gap-2">
            <div class="flex flex-col gap-1">
                <label for="tahun" class="text-xs font-medium text-[#567C8D]">Periode</label>
                <select id="tahun" name="tahun" class="rounded-lg border border-[#C8D9E6] bg-white px-3 py-2 text-sm text-[#2F4156] focus:border-[#567C8D] focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30">
                    @foreach($availableYears as $availableYear)
                        <option value="{{ $availableYear }}" @selected((int) $year === (int) $availableYear)>{{ $availableYear }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="pbtn pbtn-primary">Tampilkan</button>
        </form>
    </div>

    @if($undatedResignCount > 0)
        <div class="flex items-start gap-3 rounded-xl border border-[#F3D28A] bg-[#FFF8E8] p-4 text-sm text-[#79520A]">
            <svg class="mt-0.5 h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M10.3 3.9 2.5 17.4A2 2 0 0 0 4.2 20.4h15.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/>
                <path d="M12 9v4m0 4h.01"/>
            </svg>
            <p><span class="font-semibold">{{ number_format($undatedResignCount) }} karyawan resign belum memiliki tanggal keluar.</span> Data tersebut tetap ditampilkan di riwayat, tetapi belum dapat masuk ke perhitungan tren per periode.</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-[#C8D9E6] bg-white p-4 shadow-[0_1px_4px_rgba(47,65,86,.06)]">
            <p class="text-xs font-medium text-[#567C8D]">Karyawan aktif saat ini</p>
            <p class="mt-2 text-2xl font-semibold text-[#2F4156]">{{ number_format($activeEmployeeCount) }}</p>
            <p class="mt-1 text-xs text-[#8BAFC4]">Status aktif di database</p>
        </div>
        <div class="rounded-xl border border-[#C8D9E6] bg-white p-4 shadow-[0_1px_4px_rgba(47,65,86,.06)]">
            <p class="text-xs font-medium text-[#567C8D]">Karyawan masuk · {{ $year }}</p>
            <p class="mt-2 text-2xl font-semibold text-[#1B7A4A]">{{ number_format($employeeStartCount) }}</p>
            <p class="mt-1 text-xs text-[#8BAFC4]">Berdasarkan tanggal masuk</p>
        </div>
        <div class="rounded-xl border border-[#C8D9E6] bg-white p-4 shadow-[0_1px_4px_rgba(47,65,86,.06)]">
            <p class="text-xs font-medium text-[#567C8D]">Karyawan resign · {{ $year }}</p>
            <p class="mt-2 text-2xl font-semibold text-[#B91C1C]">{{ number_format($employeeResignCount) }}</p>
            <p class="mt-1 text-xs text-[#8BAFC4]">Resign dengan tanggal tercatat</p>
        </div>
        <div class="rounded-xl border border-[#F3D28A] bg-white p-4 shadow-[0_1px_4px_rgba(47,65,86,.06)]">
            <p class="text-xs font-medium text-[#567C8D]">Tanggal resign belum tercatat</p>
            <p class="mt-2 text-2xl font-semibold text-[#9A6200]">{{ number_format($undatedResignCount) }}</p>
            <p class="mt-1 text-xs text-[#8BAFC4]">Karyawan nonaktif tanpa tanggal keluar</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <section class="rounded-xl border border-[#C8D9E6] bg-white p-5 shadow-[0_1px_4px_rgba(47,65,86,.06)] xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-[#2F4156]">Pergerakan karyawan</h2>
                    <p class="mt-1 text-xs text-[#8BAFC4]">
                        Masuk, resign, dan net per bulan pada {{ $year }}.
                        Net: <span class="font-semibold {{ $employeeStartCount - $employeeResignCount >= 0 ? 'text-[#1B7A4A]' : 'text-[#B91C1C]' }}">{{ ($employeeStartCount - $employeeResignCount) > 0 ? '+' : '' }}{{ number_format($employeeStartCount - $employeeResignCount) }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-4 text-xs text-[#567C8D]">
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#1B7A4A]"></span>Masuk</span>
                    <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-sm bg-[#B91C1C]"></span>Resign</span>
                </div>
            </div>

            <div class="relative mt-4 h-72">
                <canvas
                    id="chartPergerakan"
                    data-months="{{ json_encode($monthlyTrend->values()) }}"
                    data-year="{{ (int) $year }}"
                    role="img"
                    aria-label="Grafik masuk dan resign karyawan per bulan {{ $year }}"
                ></canvas>
            </div>
            <p class="mt-2 text-[11px] text-[#8BAFC4]">Bulan berjalan ditandai titik dan dipudarkan. Bulan yang belum berjalan disembunyikan.</p>
        </section>

        <section class="rounded-xl border border-[#C8D9E6] bg-white p-5 shadow-[0_1px_4px_rgba(47,65,86,.06)]">
            @php
                $totalResign = $reasonBreakdown->sum();
                $belum = $reasonBreakdown['Belum dicatat'] ?? 0;
                $terisi = $totalResign - $belum;
                $pct = $totalResign > 0 ? round($terisi / $totalResign * 100) : 0;
                $filled = $reasonBreakdown->except('Belum dicatat')->sortDesc();
                $maxReason = max($filled->max() ?? 1, 1);
            @endphp

            <div class="flex items-baseline justify-between gap-2">
                <h2 class="text-sm font-semibold text-[#2F4156]">Alasan resign · {{ $year }}</h2>
                <span class="text-xs text-[#567C8D]">{{ number_format($terisi) }} dari {{ number_format($totalResign) }} terisi</span>
            </div>

            @if($totalResign === 0)
                <div class="mt-5 rounded-lg bg-[#F9FBFD] p-4 text-center text-xs text-[#8BAFC4]">Belum ada data resign pada periode ini.</div>
            @else
                <div class="mt-3 h-1.5 rounded-full bg-[#EAF1F6]">
                    <div class="h-full rounded-full bg-[#B91C1C]" style="width: {{ max($pct, 2) }}%"></div>
                </div>

                @if($belum > 0)
                    <p class="mt-2 text-xs text-[#9A6200]">{{ number_format($belum) }} resign belum punya alasan. Lengkapi supaya distribusi akurat.</p>
                @endif

                @if($filled->isNotEmpty())
                    <ul class="mt-4 space-y-3">
                        @foreach($filled as $reason => $count)
                            <li>
                                <div class="flex justify-between gap-3 text-sm">
                                    <span class="truncate text-[#567C8D]" title="{{ $reason }}">{{ $reason }}</span>
                                    <span class="font-semibold text-[#2F4156]">{{ number_format($count) }}</span>
                                </div>
                                <div class="mt-1 h-1.5 rounded-full bg-[#EAF1F6]">
                                    <div class="h-full rounded-full bg-[#B91C1C]" style="width: {{ $count / $maxReason * 100 }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </section>
    </div>

    <section class="overflow-hidden rounded-xl border border-[#C8D9E6] bg-white shadow-[0_1px_4px_rgba(47,65,86,.06)]">
        <div class="flex flex-wrap items-start justify-between gap-2 border-b border-[#EAF1F6] px-5 py-4">
            <div>
                <h2 class="text-sm font-semibold text-[#2F4156]">Riwayat karyawan keluar</h2>
                <p class="mt-1 text-xs text-[#8BAFC4]">Menampilkan resign pada {{ $year }} dan semua karyawan nonaktif yang tanggal keluarnya belum tercatat.</p>
            </div>
            <span class="text-xs text-[#8BAFC4]">{{ number_format($employeeExits->total()) }} data</span>
        </div>

        <form method="GET" action="{{ route('turnover.index') }}" class="flex flex-wrap items-end gap-3 border-b border-[#EAF1F6] px-5 py-4">
            <input type="hidden" name="tahun" value="{{ $year }}">
            <div class="flex min-w-[220px] flex-1 flex-col gap-1">
                <label for="searchRiwayat" class="text-xs font-medium text-[#567C8D]">Cari karyawan</label>
                <input id="searchRiwayat" name="search" type="search" value="{{ $search }}"
                    placeholder="Nama, NIP, atau PIN"
                    class="rounded-lg border border-[#C8D9E6] bg-white px-3 py-2 text-sm text-[#2F4156] focus:border-[#567C8D] focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30">
            </div>
            <div class="flex min-w-[180px] flex-col gap-1">
                <label for="filterBagianRiwayat" class="text-xs font-medium text-[#567C8D]">Bagian</label>
                <select id="filterBagianRiwayat" name="bagian" class="rounded-lg border border-[#C8D9E6] bg-white px-3 py-2 text-sm text-[#2F4156] focus:border-[#567C8D] focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30">
                    <option value="">Semua Bagian</option>
                    @foreach($bagianList as $bagian)
                        <option value="{{ $bagian }}" @selected($selectedBagian === $bagian)>{{ $bagian }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="pbtn pbtn-primary">Cari</button>
            @if($search !== '' || $selectedBagian !== '')
                <a href="{{ route('turnover.index', ['tahun' => $year]) }}" class="pbtn pbtn-secondary">Reset</a>
            @endif
        </form>

        <form method="POST" action="{{ route('turnover.update') }}" class="space-y-0">
            @csrf
            @method('PUT')
            <input type="hidden" name="tahun" value="{{ $year }}">
            <input type="hidden" name="search" value="{{ $search }}">
            <input type="hidden" name="bagian" value="{{ $selectedBagian }}">

            @if($errors->any())
                <div class="mx-5 mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if(session('success'))
                <div class="mx-5 mt-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700">{{ session('success') }}</div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
                <p class="text-xs text-[#567C8D]"><span id="turnoverSelectedCount">0</span> karyawan dipilih. Isian tanggal dan alasan berlaku per karyawan.</p>
                <button id="turnoverSaveButton" type="submit" disabled class="pbtn pbtn-primary disabled:cursor-not-allowed disabled:opacity-50">Simpan perubahan</button>
            </div>

            @php
                $selectedIds = array_map('strval', old('ids', []));
            @endphp
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead class="bg-[#F8FAFC] text-[11px] font-medium uppercase tracking-wide text-[#8BAFC4]">
                        <tr>
                            <th class="px-4 py-3">
                                <input id="turnoverSelectAll" type="checkbox" aria-label="Pilih semua karyawan di halaman ini" class="accent-[#567C8D]">
                            </th>
                            <th class="px-4 py-3">Nama karyawan</th>
                            <th class="px-4 py-3">NIP</th>
                            <th class="px-4 py-3">Bagian</th>
                            <th class="px-4 py-3">Tanggal keluar</th>
                            <th class="px-4 py-3">Alasan resign</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EAF1F6]">
                        @forelse($employeeExits as $employee)
                            @php
                                $isSelected = in_array((string) $employee->id, $selectedIds, true);
                                $savedReasonType = array_search($employee->alasan_resign, $resignReasons, true);
                                $reasonType = old('employees.'.$employee->id.'.alasan_resign', $savedReasonType !== false ? $savedReasonType : ($employee->alasan_resign ? 'other' : ''));
                                $reasonOther = old('employees.'.$employee->id.'.alasan_lainnya', $reasonType === 'other' ? $employee->alasan_resign : '');
                            @endphp
                            <tr class="turnover-employee-row cursor-pointer text-[#2F4156] transition-colors hover:bg-[#F8FAFC]">
                                <td class="px-4 py-3">
                                    <input type="checkbox" name="ids[]" value="{{ $employee->id }}" class="turnover-employee-check accent-[#567C8D]" @checked($isSelected)>
                                </td>
                                <td class="px-4 py-3 font-medium">{{ $employee->nama }}</td>
                                <td class="px-4 py-3 text-[#567C8D]">{{ $employee->nip ?: '-' }}</td>
                                <td class="px-4 py-3 text-[#567C8D]">{{ $employee->bagian ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <input type="date" name="employees[{{ $employee->id }}][tanggal_resign]"
                                        value="{{ old('employees.'.$employee->id.'.tanggal_resign', $employee->tanggal_resign?->format('Y-m-d')) }}"
                                        min="{{ $employee->tanggal_masuk?->format('Y-m-d') }}" max="{{ now()->toDateString() }}"
                                        class="turnover-employee-field w-40 rounded-lg border border-[#C8D9E6] bg-white px-2 py-1.5 text-sm focus:border-[#567C8D] focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30"
                                        @disabled(! $isSelected)>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex min-w-[300px] flex-col gap-2">
                                        <select name="employees[{{ $employee->id }}][alasan_resign]"
                                            class="turnover-employee-field turnover-reason-select rounded-lg border border-[#C8D9E6] bg-white px-2 py-1.5 text-sm focus:border-[#567C8D] focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30"
                                            @disabled(! $isSelected)>
                                            <option value="">Pilih alasan</option>
                                            @foreach($resignReasons as $reasonValue => $reasonLabel)
                                                <option value="{{ $reasonValue }}" @selected($reasonType === $reasonValue)>{{ $reasonLabel }}</option>
                                            @endforeach
                                            <option value="other" @selected($reasonType === 'other')>Alasan lainnya</option>
                                        </select>
                                        <input type="text" name="employees[{{ $employee->id }}][alasan_lainnya]"
                                            value="{{ $reasonOther }}" maxlength="255" placeholder="Tuliskan alasan resign"
                                            class="turnover-reason-other rounded-lg border border-[#C8D9E6] bg-white px-2 py-1.5 text-sm focus:border-[#567C8D] focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30"
                                            @if($reasonType !== 'other') hidden @endif
                                            @required($isSelected && $reasonType === 'other')
                                            @disabled(! $isSelected)>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-[#8BAFC4]">Belum ada data karyawan keluar untuk ditampilkan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end border-t border-[#EAF1F6] px-5 py-4">
                <button type="submit" disabled class="turnover-save-button pbtn pbtn-primary disabled:cursor-not-allowed disabled:opacity-50">Simpan perubahan</button>
            </div>
        </form>

        @if($employeeExits->hasPages())
            <div class="border-t border-[#EAF1F6] px-5 py-3">
                {{ $employeeExits->links() }}
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
@vite('resources/js/turnover.js')
@endpush
