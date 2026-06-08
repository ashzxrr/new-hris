@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-semibold text-slate-800">Data Karyawan</h1>
    <div class="flex items-center gap-2">
        <span id="selectedCount" class="text-xs text-slate-400 hidden">
            <span id="selectedNum">0</span> dipilih
        </span>
        <button type="button" id="btnEdit" onclick="submitEdit()"
            disabled
            class="text-xs px-3 py-1.5 rounded-lg border border-[#E5E7EB] text-slate-400 cursor-not-allowed transition">
            ✏️ Edit
        </button>
        <button type="button" id="btnResign" onclick="submitResign()"
            disabled
            class="text-xs px-3 py-1.5 rounded-lg border border-[#E5E7EB] text-slate-400 cursor-not-allowed transition">
            🚫 Resign
        </button>
        <a href="{{ route('karyawan.sync') }}"
            class="text-xs px-3 py-1.5 rounded-lg bg-[#4F46E5] text-white hover:bg-[#4338CA] transition">
            🔄 Sinkron dari Mesin
        </a>
    </div>
</div>

<div class="mt-6 space-y-4">
    <div>
        <input
            id="searchInput"
            type="text"
            placeholder="Cari nama, NIP, atau PIN..."
            class="w-72 border border-[#E5E7EB] rounded-lg px-4 py-2 text-sm"
        />
    </div>

    @php
        $bagianList = $karyawan->pluck('bagian')->filter(fn($value) => trim($value) !== '')->unique()->sort()->values();
        $kategoriList = $karyawan->pluck('kategori_gaji')->filter(fn($value) => trim($value) !== '')->unique()->sort()->values();
    @endphp

    <div class="grid grid-cols-3 gap-4">
        <div class="flex flex-col">
            <label class="text-xs text-slate-400 uppercase tracking-wide font-semibold block mb-1">Bagian</label>
            <select id="filterBagian" class="w-full border border-[#E5E7EB] rounded-lg px-3 py-1.5 text-sm bg-[#F8FAFC] focus:outline-none focus:ring-2 focus:ring-slate-300">
                <option value="">Semua Bagian</option>
                @foreach($bagianList as $bagian)
                    <option value="{{ $bagian }}">{{ $bagian }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col">
            <label class="text-xs text-slate-400 uppercase tracking-wide font-semibold block mb-1">TL</label>
            <div class="relative" id="tlDropdownWrapper">
                <button type="button" id="tlDropdownBtn"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-1.5 text-sm bg-[#F8FAFC] text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-slate-300">
                    <span id="tlFilterLabel" class="text-slate-500 truncate">Semua TL</span>
                    <svg class="w-4 h-4 text-slate-400 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div id="tlDropdownMenu" class="hidden absolute z-30 mt-1 w-56 bg-white border border-[#E5E7EB] rounded-xl shadow-lg max-h-64 overflow-y-auto p-3">
                    <button type="button" onclick="clearTLFilter()"
                        class="w-full text-left px-3 py-1.5 text-xs text-slate-500 bg-[#F8FAFC] hover:bg-[#F8FAFC] rounded-lg mb-2">
                        Bersihkan Pilihan
                    </button>

                    <div class="mb-3">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide px-1 mb-1">Cabut</div>
                        @foreach([['id'=>8,'nama'=>'Karyawati'],['id'=>3,'nama'=>'Sri Utami'],['id'=>2,'nama'=>'ST Nur Farokah'],['id'=>25,'nama'=>'Fhilis Sulestari'],['id'=>22,'nama'=>'Muhammad Regatana Hidayatulloh'],['id'=>119,'nama'=>'Zusita Arsdhia Indrayani'],['id'=>34,'nama'=>'Wahyu Surodo'],['id'=>30,'nama'=>'Deniko Fergian'],['id'=>109,'nama'=>'Ruliatul Fidiah']] as $tl)
                        <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-[#F8FAFC] rounded-lg cursor-pointer">
                            <input type="checkbox" class="tl-filter-check accent-[#4F46E5]" value="{{ $tl['id'] }}" data-nama="{{ $tl['nama'] }}">
                            <span class="text-sm text-slate-700">{{ $tl['nama'] }}</span>
                        </label>
                        @endforeach
                    </div>

                    <div class="mb-3">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide px-1 mb-1">Cetak</div>
                        @foreach([['id'=>57,'nama'=>'Muhammad Tamamur Ridlwan'],['id'=>7,'nama'=>'Anita'],['id'=>74,'nama'=>'Nur Alim Zainuri'],['id'=>27,'nama'=>"Anas Ja'far"],['id'=>48,'nama'=>'M.Jamaludin'],['id'=>99,'nama'=>'Nila Widya Sari'],['id'=>113,'nama'=>'Nurul Izzuddin'],['id'=>75,'nama'=>'Niko Yudho'],['id'=>71,'nama'=>'Tsalis Akmaludin'],['id'=>69,'nama'=>'Prayogo Dwi']] as $tl)
                        <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-[#F8FAFC] rounded-lg cursor-pointer">
                            <input type="checkbox" class="tl-filter-check accent-[#4F46E5]" value="{{ $tl['id'] }}" data-nama="{{ $tl['nama'] }}">
                            <span class="text-sm text-slate-700">{{ $tl['nama'] }}</span>
                        </label>
                        @endforeach
                    </div>

                    <div class="mb-1">
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide px-1 mb-1">Dan Lain Lain</div>
                        @foreach([['id'=>1,'nama'=>'Anik'],['id'=>98,'nama'=>'M Gaung Sidiq'],['id'=>40,'nama'=>'Cankiswan'],['id'=>118,'nama'=>'Kerinna'],['id'=>63,'nama'=>'Puput Indarwati'],['id'=>865,'nama'=>'TL CCP 1'],['id'=>871,'nama'=>'Sanitasi'],['id'=>872,'nama'=>'Checker'],['id'=>43,'nama'=>'GD Kart']] as $tl)
                        <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-[#F8FAFC] rounded-lg cursor-pointer">
                            <input type="checkbox" class="tl-filter-check accent-[#4F46E5]" value="{{ $tl['id'] }}" data-nama="{{ $tl['nama'] }}">
                            <span class="text-sm text-slate-700">{{ $tl['nama'] }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col">
            <label class="text-xs text-slate-400 uppercase tracking-wide font-semibold block mb-1">Kategori Gaji</label>
            <select id="filterKategori" class="w-full border border-[#E5E7EB] rounded-lg px-3 py-1.5 text-sm bg-[#F8FAFC] focus:outline-none focus:ring-2 focus:ring-slate-300">
                <option value="">Semua Kategori</option>
                @foreach($kategoriList as $kategori)
                    <option value="{{ $kategori }}">{{ $kategori }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>

<div class="mt-4 bg-white rounded-xl border border-[#E5E7EB] overflow-hidden">
    <div class="overflow-auto max-h-[70vh]">
        <style>
            .karyawan-row:hover { background-color: #f0fdf4 !important; }
            .karyawan-row:hover td { background-color: #f0fdf4 !important; }
            .karyawan-row.selected { background-color: #dcfce7 !important; }
            .karyawan-row.selected td { background-color: #dcfce7 !important; }
        </style>
        <table class="w-full text-xs whitespace-nowrap min-w-[1400px]">
            <thead>
                <tr class="sticky top-0 bg-[#F8FAFC] z-20 text-[11px] font-medium text-slate-400 uppercase tracking-wide">
                    <th class="px-2 py-2 sticky left-0 bg-[#F8FAFC] z-20 border-r border-[#E5E7EB] w-8">
                        <input type="checkbox" id="checkAll" class="accent-[#4F46E5]" onclick="toggleAll(this)">
                    </th>
                    <th class="px-2 py-2 text-left sticky left-8 bg-[#F8FAFC] z-20 border-r border-[#E5E7EB] w-12">ID</th>
                    <th class="px-2 py-1.5 text-left sticky left-16 bg-[#F8FAFC] z-20 border-r border-[#E5E7EB] w-14">PIN</th>
                    <th class="px-2 py-2 text-left sticky left-28 bg-[#F8FAFC] z-20 border-r border-[#E5E7EB] min-w-[140px]">Nama (Mesin)</th>
                    <th class="px-2 py-2 text-left sticky left-[196px] bg-[#F8FAFC] z-20 border-r border-[#E5E7EB] min-w-[160px]">Nama (Database)</th>
                    <th class="px-2 py-2 text-left sticky left-[356px] bg-[#F8FAFC] z-20 border-r border-[#E5E7EB] min-w-[130px]">NIP</th>
                    <th class="px-2 py-2 text-left">NIK</th>
                    <th class="px-2 py-2 text-left">Gender</th>
                    <th class="px-2 py-2 text-left">Jabatan</th>
                    <th class="px-2 py-2 text-left">Level</th>
                    <th class="px-2 py-2 text-left">Bagian</th>
                    <th class="px-2 py-2 text-left">Departemen</th>
                    <th class="px-2 py-2 text-left">TL</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
            @forelse ($karyawan as $k)
                <tr class="karyawan-row border-t border-slate-50 cursor-pointer transition-colors duration-100" onclick="toggleRow(this)" data-bagian="{{ $k->bagian }}" data-kategori="{{ $k->kategori_gaji }}" data-tl-id="{{ $k->tl_id }}" data-search="{{ strtolower($k->pin . ' ' . $k->nama . ' ' . $k->nip . ' ' . $k->nik . ' ' . $k->bagian . ' ' . $k->job_title) }}">
                    <td class="px-2 py-1.5 sticky left-0 bg-white z-10 border-r border-[#E5E7EB]" onclick="event.stopPropagation()">
                        <input type="checkbox" class="karyawan-check accent-[#4F46E5]" value="{{ $k->id }}" data-pin="{{ $k->pin }}" onchange="updateSelectedCount()">
                    </td>
                    <td class="px-2 py-1.5 sticky left-8 bg-white z-10 border-r border-[#E5E7EB] text-slate-400">{{ $k->id }}</td>
                    <td class="px-2 py-1.5 sticky left-16 bg-white z-10 border-r border-[#E5E7EB] font-mono text-slate-400">{{ $k->pin }}</td>
                    <td class="px-2 py-1.5 sticky left-28 bg-white z-10 border-r border-[#E5E7EB] text-slate-600 uppercase min-w-[140px]">{{ strtoupper($machineUsers[$k->pin] ?? '-') }}</td>
                    <td class="px-2 py-1.5 sticky left-[196px] bg-white z-10 border-r border-[#E5E7EB] font-medium text-slate-800 min-w-[160px]">{{ $k->nama }}</td>
                    <td class="px-2 py-1.5 sticky left-[356px] bg-white z-10 border-r border-[#E5E7EB] text-slate-600 min-w-[130px]">{{ $k->nip ?: '-' }}</td>
                    <td class="px-2 py-1.5 text-slate-600">{{ $k->nik ?: '-' }}</td>
                    <td class="px-2 py-1.5 text-slate-600">{{ $k->jk === 'L' ? 'L' : 'P' }}</td>
                    <td class="px-2 py-1.5 text-slate-600">{{ $k->job_title ?: '-' }}</td>
                    <td class="px-2 py-1.5 text-slate-600">{{ $k->job_level ?: '-' }}</td>
                    <td class="px-2 py-1.5 text-slate-600">{{ $k->bagian ?: '-' }}</td>
                    <td class="px-2 py-1.5 text-slate-600">{{ $k->departemen ?: '-' }}</td>
                    <td class="px-2 py-1.5 text-slate-600">{{ $tlMap[$k->tl_id] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-8 text-center text-sm text-slate-400" colspan="14">
                        Belum ada data karyawan.
                    </td>3
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="flex items-center justify-between mt-4">
    <div class="text-xs text-slate-400" id="paginationInfo"></div>
    <div class="flex items-center gap-1" id="paginationButtons"></div>
</div>

<form id="editBulkForm" method="GET" action="{{ route('karyawan.editBulk') }}">
    <div id="editInputs"></div>
</form>

<form id="resignForm" method="POST" action="{{ route('karyawan.resignBulk') }}">
    @csrf
    @method('PUT')
    <div id="resignInputs"></div>
</form>

<div id="editModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl mx-4 p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-semibold text-slate-800" id="editModalTitle">Edit Karyawan</h3>
            <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
        </div>

        <form id="editForm" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-3 gap-4">

                <div class="col-span-1">
                    <label class="text-xs text-slate-500 mb-1 block">PIN</label>
                    <input type="text" id="edit_pin" readonly
                        class="w-full bg-[#F8FAFC] border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm text-slate-400">
                </div>

                <div class="col-span-2">
                    <label class="text-xs text-slate-500 mb-1 block">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" id="edit_nama" required
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">NIP</label>
                    <input type="text" name="nip" id="edit_nip"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">NIK</label>
                    <input type="text" name="nik" id="edit_nik"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Jenis Kelamin</label>
                    <select name="jk" id="edit_jk"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Pilih -</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Jabatan</label>
                    <select name="job_title" id="edit_job_title"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Pilih -</option>
                        @foreach(['TL Cuci','TL Cabut','TL Kedatangan','Operator','SPV Moulding','TL Moulding','GTL Moulding','GTL Cabut','Driver','Manager Produksi','SPV Kedatangan','Checker Cabut','Admin Produktivitas','Checker Moulding','TL Pengiriman','Admin','TL Packing','Superintenden','Ass. Superintenden','TL Cutter & Flek','SPV Packing','Security','Sanitasi','Purchasing/ Logistic','Maintenance','Finance Accounting','General Affair','HRD','Payroll','GTL Packing'] as $jt)
                        <option value="{{ $jt }}">{{ $jt }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Level Jabatan</label>
                    <select name="job_level" id="edit_job_level"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Pilih -</option>
                        @foreach(['Operator','Team Leader','Supervisor','Group Team Leader','Manager','Checker','Administrasi','Driver','Superintenden','General Manager','Security','Sanitasi','Maintenance','Finance Accounting','General Affair','HRD','Payroll'] as $jl)
                        <option value="{{ $jl }}">{{ $jl }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Bagian</label>
                    <select name="bagian" id="edit_bagian"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Pilih -</option>
                        @foreach(['-','Manager Produksi','Bahan Baku','Cabut','Dry A','Moulding','Cuci Bersih','Cuci Kotor','Admin','Rambang','Cutter & Flek','Dry B & HCR','HCR Moulding','Admin Cabut & Bahan Baku','Packing','Admin Drying & Moulding','SPV','TL Pre Cleaning','Checker Moulding','Timbang Indomie','Administrasi','Grading','Final Grading','Titil HCR','Moulding Indomie','CCP 1','Prewash','Driver','Admin Packing','Admin Cabut','Security','Sanitasi','Kasir Perusahaan','Maintenance IT','Finance Accounting','Maintenance','Borongan','Bulanan','Harian'] as $bg)
                        <option value="{{ $bg }}">{{ $bg }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Departemen</label>
                    <select name="departemen" id="edit_departemen"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Pilih -</option>
                        <option value="Produksi">Produksi</option>
                        <option value="Support">Support</option>
                        <option value="Operation">Operation</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Kategori Gaji</label>
                    <select name="kategori_gaji" id="edit_kategori_gaji"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Pilih -</option>
                        <option value="borongan cabut">Borongan Cabut</option>
                        <option value="borongan cetak">Borongan Cetak</option>
                        <option value="harian">Harian</option>
                        <option value="bulanan">Bulanan</option>
                    </select>
                </div>

                <div class="col-span-3">
                    <label class="text-xs text-slate-500 mb-1 block">TL (Team Leader)</label>
                    <select name="tl_id" id="edit_tl_id"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Tidak Ada -</option>
                        <optgroup label="CABUT">
                            @foreach([['id'=>8,'nama'=>'Karyawati'],['id'=>3,'nama'=>'Sri Utami'],['id'=>2,'nama'=>'ST Nur Farokah'],['id'=>25,'nama'=>'Fhilis Sulestari'],['id'=>22,'nama'=>'Muhammad Regatana Hidayatulloh'],['id'=>119,'nama'=>'Zusita Arsdhia Indrayani'],['id'=>34,'nama'=>'Wahyu Surodo'],['id'=>30,'nama'=>'Deniko Fergian'],['id'=>109,'nama'=>'Ruliatul Fidiah']] as $tl)
                            <option value="{{ $tl['id'] }}">{{ $tl['nama'] }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="CETAK">
                            @foreach([['id'=>57,'nama'=>'Muhammad Tamamur Ridlwan'],['id'=>7,'nama'=>'Anita'],['id'=>74,'nama'=>'Nur Alim Zainuri'],['id'=>27,'nama'=>"Anas Ja'far"],['id'=>48,'nama'=>'M.Jamaludin'],['id'=>99,'nama'=>'Nila Widya Sari'],['id'=>113,'nama'=>'Nurul Izzuddin'],['id'=>75,'nama'=>'Niko Yudho'],['id'=>71,'nama'=>'Tsalis Akmaludin'],['id'=>69,'nama'=>'Prayogo Dwi']] as $tl)
                            <option value="{{ $tl['id'] }}">{{ $tl['nama'] }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="DAN LAIN LAIN">
                            @foreach([['id'=>1,'nama'=>'Anik'],['id'=>98,'nama'=>'M Gaung Sidiq'],['id'=>40,'nama'=>'Cankiswan'],['id'=>118,'nama'=>'Kerinna'],['id'=>63,'nama'=>'Puput Indarwati'],['id'=>865,'nama'=>'TL CCP 1'],['id'=>871,'nama'=>'Sanitasi'],['id'=>872,'nama'=>'Checker'],['id'=>43,'nama'=>'GD Kart']] as $tl)
                            <option value="{{ $tl['id'] }}">{{ $tl['nama'] }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

            </div>

            <div class="flex gap-3 justify-end mt-6">
                <button type="button" onclick="closeEditModal()"
                    class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm hover:bg-[#F8FAFC]">
                    Batal
                </button>
                <button type="submit"
                    class="bg-[#4F46E5] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#4338CA]">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const ROWS_PER_PAGE = 50;
    let currentPage = 1;
    let filteredRows = [];
    let originalRows = [];

    document.addEventListener('DOMContentLoaded', function() {
        originalRows = Array.from(document.querySelectorAll('.karyawan-row'));
        filteredRows = [...originalRows];
        if (typeof renderPage === 'function') renderPage(1);
    });

    function getTLFilteredBase() {
        const checkedTLs = Array.from(document.querySelectorAll('.tl-filter-check:checked, .tl-check:checked')).map(c => c.value);

        if (checkedTLs.length === 0) return [...originalRows];

        const grouped = {};
        checkedTLs.forEach(tlId => grouped[tlId] = []);

        originalRows.forEach(row => {
            const tlId = (row.dataset.tlId || '').toString();
            if (grouped.hasOwnProperty(tlId)) {
                grouped[tlId].push(row);
            }
        });

        const result = [];
        checkedTLs.forEach(tlId => {
            grouped[tlId].forEach(r => result.push(r));
        });
        return result;
    }

    function updateTLLabel() {
        const checkedTLs = Array.from(document.querySelectorAll('.tl-filter-check:checked, .tl-check:checked'));
        const label = document.getElementById('tlFilterLabel') || document.getElementById('tlDropdownLabel');
        if (!label) return;

        if (checkedTLs.length === 0) {
            label.textContent = 'Semua TL';
            label.classList.add('text-slate-500');
            label.classList.remove('text-slate-800');
        } else if (checkedTLs.length === 1) {
            label.textContent = checkedTLs[0].dataset.nama;
            label.classList.remove('text-slate-500');
            label.classList.add('text-slate-800');
        } else {
            label.textContent = checkedTLs.length + ' TL dipilih';
            label.classList.remove('text-slate-500');
            label.classList.add('text-slate-800');
        }
    }

    function applyAllFilters() {
        updateTLLabel();

        const q = (document.getElementById('searchInput') || document.getElementById('searchKaryawan'))?.value.toLowerCase().trim() || '';
        const bagian = document.getElementById('filterBagian')?.value.toLowerCase() || '';
        const kategori = document.getElementById('filterKategori')?.value.toLowerCase() || '';

        const baseRows = getTLFilteredBase();

        const matched = baseRows.filter(row => {
            const matchSearch = !q || row.dataset.search?.includes(q) || row.textContent.toLowerCase().includes(q);
            const matchBagian = !bagian || (row.dataset.bagian || '').toLowerCase() === bagian;
            const matchKategori = !kategori || (row.dataset.kategori || '').toLowerCase() === kategori;
            return matchSearch && matchBagian && matchKategori;
        });

        const container = document.querySelector('tbody') || document.getElementById('karyawanList');
        const unmatched = originalRows.filter(r => !matched.includes(r));
        [...matched, ...unmatched].forEach(row => container.appendChild(row));

        if (typeof filteredRows !== 'undefined') {
            filteredRows = matched;
            unmatched.forEach(row => row.style.display = 'none');
            renderPage(1);
        } else {
            originalRows.forEach(row => {
                row.style.display = matched.includes(row) ? '' : 'none';
            });
            if (typeof updateSelectedCount === 'function') updateSelectedCount();
        }
    }

    function clearTLFilter() {
        document.querySelectorAll('.tl-filter-check').forEach(c => c.checked = false);
        document.querySelectorAll('.tl-check').forEach(c => c.checked = false);

        updateTLLabel();

        const container = document.querySelector('tbody') || document.getElementById('karyawanList');
        originalRows.forEach(row => container.appendChild(row));

        if (typeof filteredRows !== 'undefined') {
            filteredRows = [...originalRows];
            renderPage(1);
        } else {
            originalRows.forEach(row => {
                row.style.display = '';
            });
            if (typeof updateSelectedCount === 'function') updateSelectedCount();
            document.querySelectorAll('.tl-order-input').forEach(e => e.remove());
        }
    }

    document.querySelectorAll('.tl-filter-check, .tl-check').forEach(cb => {
        cb.addEventListener('change', function() {
            if (document.getElementById('karyawanList')) {
                document.querySelectorAll('.tl-order-input').forEach(e => e.remove());
                const checkedTLs = Array.from(document.querySelectorAll('.tl-check:checked')).map(c => c.value);
                checkedTLs.forEach(function(tlId) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'tl_order[]';
                    input.value = tlId;
                    input.classList.add('tl-order-input');
                    document.querySelector('form')?.appendChild(input);
                });
            }

            applyAllFilters();
        });
    });

    document.getElementById('filterBagian')?.addEventListener('change', applyAllFilters);
    document.getElementById('filterKategori')?.addEventListener('change', applyAllFilters);

    (document.getElementById('searchInput') || document.getElementById('searchKaryawan'))
        ?.addEventListener('input', applyAllFilters);

    document.getElementById('tlDropdownBtn')?.addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('tlDropdownMenu')?.classList.toggle('hidden');
    });

    document.addEventListener('click', function() {
        document.getElementById('tlDropdownMenu')?.classList.add('hidden');
    });

    document.getElementById('tlDropdownMenu')?.addEventListener('click', e => e.stopPropagation());

    function toggleRow(row) {
        const cb = row.querySelector('.karyawan-check');
        if (!cb) return;
        cb.checked = !cb.checked;
        updateRowStyle(row);
        updateSelectedCount();
    }

    function updateRowStyle(row) {
        const cb = row.querySelector('.karyawan-check');
        if (cb && cb.checked) {
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
    }

    function renderPage(page) {
        currentPage = page;
        const start = (page - 1) * ROWS_PER_PAGE;
        const end = start + ROWS_PER_PAGE;

        filteredRows.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });

        updatePaginationInfo();
        updatePaginationButtons();
    }

    function updatePaginationInfo() {
        const total = filteredRows.length;
        const start = total === 0 ? 0 : (currentPage - 1) * ROWS_PER_PAGE + 1;
        const end = Math.min(currentPage * ROWS_PER_PAGE, total);
        document.getElementById('paginationInfo').textContent =
            'Menampilkan ' + start + '–' + end + ' dari ' + total + ' karyawan';
    }

    function updatePaginationButtons() {
        const totalPages = Math.ceil(filteredRows.length / ROWS_PER_PAGE);
        const container = document.getElementById('paginationButtons');
        container.innerHTML = '';

        if (totalPages <= 1) return;

        const prev = document.createElement('button');
        prev.textContent = '←';
        prev.disabled = currentPage === 1;
        prev.className = 'px-2 py-1 text-xs rounded border border-[#E5E7EB] text-slate-500 hover:bg-[#F8FAFC] disabled:opacity-40';
        prev.onclick = () => renderPage(currentPage - 1);
        container.appendChild(prev);

        const range = getPageRange(currentPage, totalPages);
        range.forEach(p => {
            if (p === '...') {
                const dots = document.createElement('span');
                dots.textContent = '...';
                dots.className = 'px-2 text-xs text-slate-400';
                container.appendChild(dots);
            } else {
                const btn = document.createElement('button');
                btn.textContent = p;
                btn.className = p === currentPage
                    ? 'px-2 py-1 text-xs rounded bg-[#4F46E5] text-white border border-[#4F46E5]'
                    : 'px-2 py-1 text-xs rounded border border-[#E5E7EB] text-slate-500 hover:bg-[#F8FAFC]';
                btn.onclick = () => renderPage(p);
                container.appendChild(btn);
            }
        });

        const next = document.createElement('button');
        next.textContent = '→';
        next.disabled = currentPage === totalPages;
        next.className = 'px-2 py-1 text-xs rounded border border-[#E5E7EB] text-slate-500 hover:bg-[#F8FAFC] disabled:opacity-40';
        next.onclick = () => renderPage(currentPage + 1);
        container.appendChild(next);
    }

    function getPageRange(current, total) {
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
        if (current <= 4) return [1, 2, 3, 4, 5, '...', total];
        if (current >= total - 3) return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
        return [1, '...', current - 1, current, current + 1, '...', total];
    }

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.karyawan-check:checked');
        const count = checked.length;

        document.querySelectorAll('.karyawan-row').forEach(updateRowStyle);

        const countEl = document.getElementById('selectedNum');
        const countWrapper = document.getElementById('selectedCount');
        countEl.textContent = count;

        if (count > 0) {
            countWrapper.classList.remove('hidden');
        } else {
            countWrapper.classList.add('hidden');
        }

        const btnEdit = document.getElementById('btnEdit');
        if (count > 0) {
            btnEdit.disabled = false;
            btnEdit.className = 'text-xs px-3 py-1.5 rounded-lg border border-[#4F46E5]/30 text-[#4F46E5] hover:bg-[#4F46E5]/5 transition cursor-pointer';
        } else {
            btnEdit.disabled = true;
            btnEdit.className = 'text-xs px-3 py-1.5 rounded-lg border border-[#E5E7EB] text-slate-400 cursor-not-allowed transition';
        }

        const btnResign = document.getElementById('btnResign');
        if (count > 0) {
            btnResign.disabled = false;
            btnResign.className = 'text-xs px-3 py-1.5 rounded-lg border border-red-300 text-red-500 hover:bg-red-50 transition cursor-pointer';
        } else {
            btnResign.disabled = true;
            btnResign.className = 'text-xs px-3 py-1.5 rounded-lg border border-[#E5E7EB] text-slate-400 cursor-not-allowed transition';
        }
    }

    function toggleAll(master) {
        filteredRows.forEach(function(row) {
            const cb = row.querySelector('.karyawan-check');
            if (cb) cb.checked = master.checked;
        });
        document.querySelectorAll('.karyawan-row').forEach(updateRowStyle);
        updateSelectedCount();
    }

    function submitEdit() {
        const checked = document.querySelectorAll('.karyawan-check:checked');
        if (checked.length === 0) return;

        const container = document.getElementById('editInputs');
        container.innerHTML = '';
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
        document.getElementById('editBulkForm').submit();
    }

    function submitResign() {
        const checked = document.querySelectorAll('.karyawan-check:checked');
        if (checked.length === 0) return;

        if (!confirm(checked.length + ' karyawan akan ditandai RESIGN. Lanjutkan?')) return;

        const container = document.getElementById('resignInputs');
        container.innerHTML = '';
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });
        document.getElementById('resignForm').submit();
    }

    function clearTLFilter() {
        document.querySelectorAll('.tl-filter-check').forEach(c => c.checked = false);
        document.getElementById('tlFilterLabel').textContent = 'Semua TL';

        // Kembalikan urutan DOM ke awal
        const tbody = document.querySelector('tbody');
        originalOrder.forEach(row => tbody.appendChild(row));

        // Reset filteredRows ke semua rows
        filteredRows = [...originalOrder];

        renderPage(1);
    }

    function openEditModal(id, nama) {
        fetch('/karyawan/' + id + '/edit')
            .then(r => r.json())
            .then(data => {
                const k = data.karyawan;
                document.getElementById('editModalTitle').textContent = 'Edit — ' + k.nama;
                document.getElementById('edit_pin').value = k.pin;
                document.getElementById('edit_nama').value = k.nama;
                document.getElementById('edit_nip').value = k.nip ?? '';
                document.getElementById('edit_nik').value = k.nik ?? '';
                document.getElementById('edit_jk').value = k.jk ?? '';
                document.getElementById('edit_job_title').value = k.job_title ?? '';
                document.getElementById('edit_job_level').value = k.job_level ?? '';
                document.getElementById('edit_bagian').value = k.bagian ?? '';
                document.getElementById('edit_departemen').value = k.departemen ?? '';
                document.getElementById('edit_kategori_gaji').value = k.kategori_gaji ?? '';
                document.getElementById('edit_tl_id').value = k.tl_id ?? '';
                document.getElementById('editForm').action = '/karyawan/' + id;
                document.getElementById('editModal').classList.remove('hidden');
            });
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
</script>
@endsection
