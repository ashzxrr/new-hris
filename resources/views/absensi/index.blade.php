@extends('layouts.app')

@section('content')
    <div>
        <form method="POST" action="{{ route('absensi.detail') }}">
            @csrf

            <div class="flex justify-between items-center mb-3">
                <h1 class="text-xl font-semibold text-slate-600">Data Absensi</h1>
            </div>

            <div class="bg-white rounded-xl border border-[#E5E7EB] p-6">
                @if ($errors->any())
                    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 p-4 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="flex flex-wrap items-end gap-4 mb-5">

                    {{-- Date range --}}
                    <div>
                        
                        <div class="flex gap-2 mb-2">
                            <button id="btn_today" type="button" onclick="setRange('today')" class="text-xs px-3 py-1.5 rounded-full border border-[#E5E7EB] text-slate-500 hover:border-slate-400 hover:text-slate-700 transition">Hari Ini</button>
                            <button id="btn_yesterday" type="button" onclick="setRange('yesterday')" class="text-xs px-3 py-1.5 rounded-full border border-[#E5E7EB] text-slate-500 hover:border-slate-400 hover:text-slate-700 transition">Kemarin</button>
                            <button id="btn_this_month" type="button" onclick="setRange('this_month')" class="text-xs px-3 py-1.5 rounded-full border border-[#E5E7EB] text-slate-500 hover:border-slate-400 hover:text-slate-700 transition">Bulan Ini</button>
                            <button id="btn_last_month" type="button" onclick="setRange('last_month')" class="text-xs px-3 py-1.5 rounded-full border border-[#E5E7EB] text-slate-500 hover:border-slate-400 hover:text-slate-700 transition">Bulan Lalu</button>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex flex-col">
                                <label class="text-xs text-slate-400 mb-1">Dari</label>
                                <input type="date" name="tanggal_dari" id="tanggal_dari" value="{{ date('Y-m-d') }}"
                                    class="border border-[#E5E7EB] rounded-lg px-3 py-1.5 text-sm bg-[#F8FAFC] focus:outline-none focus:ring-2 focus:ring-slate-300 w-40">
                            </div>
                            <span class="text-slate-300 mt-4">—</span>
                            <div class="flex flex-col">
                                <label class="text-xs text-slate-400 mb-1">Sampai</label>
                                <input type="date" name="tanggal_sampai" id="tanggal_sampai" value="{{ date('Y-m-d') }}"
                                    class="border border-[#E5E7EB] rounded-lg px-3 py-1.5 text-sm bg-[#F8FAFC] focus:outline-none focus:ring-2 focus:ring-slate-300 w-40">
                            </div>
                        </div>
                    </div>

                    {{-- Divider vertical --}}
                    <div class="hidden lg:block w-px bg-[#E5E7EB] self-stretch mx-2"></div>

                    {{-- Filter Bagian --}}
                    <div class="flex flex-col">
                        
                        <select id="filterBagian" class="border border-[#E5E7EB] rounded-lg px-3 py-1.5 text-sm bg-[#F8FAFC] focus:outline-none focus:ring-2 focus:ring-slate-300 w-52">
                            <option value="">Semua Bagian</option>
                            @foreach($bagianList as $bagian)
                                <option value="{{ $bagian }}">{{ $bagian }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filter TL --}}
                    <div class="flex flex-col">
                        
                        <div class="relative" id="tlDropdownWrapper">
                            <button type="button" id="tlDropdownBtn"
                                class="border border-[#E5E7EB] rounded-lg px-3 py-1.5 text-sm bg-[#F8FAFC] text-left flex items-center justify-between focus:outline-none focus:ring-2 focus:ring-slate-300 w-80">
                                <span id="tlDropdownLabel" class="text-slate-500 truncate">Semua TL</span>
                                <svg class="w-4 h-4 text-slate-400 ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div id="tlDropdownMenu" class="hidden absolute z-20 mt-1 w-full bg-white border border-[#E5E7EB] rounded-xl shadow-lg max-h-64 overflow-y-auto p-3">
                                {{-- Bersihkan pilihan --}}
                                <button type="button" onclick="clearTLFilter()"
                                    class="w-full text-left px-3 py-1.5 text-xs text-slate-500 bg-[#F8FAFC] hover:bg-[#F8FAFC] rounded-lg mb-2">
                                    Bersihkan Pilihan
                                </button>

                                {{-- CABUT --}}
                                <div class="mb-3">
                                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide px-1 mb-1">Cabut</div>
                                    @foreach([
                                        ['id'=>8,   'nama'=>'Karyawati'],
                                        ['id'=>3,   'nama'=>'Sri Utami'],
                                        ['id'=>2,   'nama'=>'ST Nur Farokah'],
                                        ['id'=>25,  'nama'=>'Fhilis Sulestari'],
                                        ['id'=>22,  'nama'=>'Muhammad Regatana Hidayatulloh'],
                                        ['id'=>119, 'nama'=>'Zusita Arsdhia Indrayani'],
                                        ['id'=>34,  'nama'=>'Wahyu Surodo'],
                                        ['id'=>30,  'nama'=>'Deniko Fergian'],
                                        ['id'=>109, 'nama'=>'Ruliatul Fidiah'],
                                    ] as $tl)
                                    <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-[#F8FAFC] rounded-lg cursor-pointer tl-option">
                                        <input type="checkbox" class="tl-check accent-[#4F46E5]"
                                            value="{{ $tl['id'] }}" data-nama="{{ $tl['nama'] }}">
                                        <span class="text-sm text-slate-700">{{ $tl['nama'] }}</span>
                                    </label>
                                    @endforeach
                                </div>

                                {{-- CETAK --}}
                                <div class="mb-3">
                                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide px-1 mb-1">Cetak</div>
                                    @foreach([
                                        ['id'=>57,  'nama'=>'Muhammad Tamamur Ridlwan'],
                                        ['id'=>7,   'nama'=>'Anita'],
                                        ['id'=>74,  'nama'=>'Nur Alim Zainuri'],
                                        ['id'=>27,  'nama'=>"Anas Ja'far"],
                                        ['id'=>48,  'nama'=>'M.Jamaludin'],
                                        ['id'=>99,  'nama'=>'Nila Widya Sari'],
                                        ['id'=>113, 'nama'=>'Nurul Izzuddin'],
                                        ['id'=>75,  'nama'=>'Niko Yudho'],
                                        ['id'=>71,  'nama'=>'Tsalis Akmaludin'],
                                        ['id'=>69,  'nama'=>'Prayogo Dwi'],
                                    ] as $tl)
                                    <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-[#F8FAFC] rounded-lg cursor-pointer tl-option">
                                        <input type="checkbox" class="tl-check accent-[#4F46E5]"
                                            value="{{ $tl['id'] }}" data-nama="{{ $tl['nama'] }}">
                                        <span class="text-sm text-slate-700">{{ $tl['nama'] }}</span>
                                    </label>
                                    @endforeach
                                </div>

                                {{-- DAN LAIN LAIN --}}
                                <div class="mb-1">
                                    <div class="text-xs font-semibold text-slate-400 uppercase tracking-wide px-1 mb-1">Dan Lain Lain</div>
                                    @foreach([
                                        ['id'=>1,   'nama'=>'Anik'],
                                        ['id'=>98,  'nama'=>'M Gaung Sidiq'],
                                        ['id'=>40,  'nama'=>'Cankiswan'],
                                        ['id'=>118, 'nama'=>'Kerinna'],
                                        ['id'=>63,  'nama'=>'Puput Indarwati'],
                                        ['id'=>865, 'nama'=>'TL CCP 1'],
                                        ['id'=>871, 'nama'=>'Sanitasi'],
                                        ['id'=>872, 'nama'=>'Checker'],
                                        ['id'=>43,  'nama'=>'GD Kart'],
                                    ] as $tl)
                                    <label class="flex items-center gap-2 px-2 py-1.5 hover:bg-[#F8FAFC] rounded-lg cursor-pointer tl-option">
                                        <input type="checkbox" class="tl-check accent-[#4F46E5]"
                                            value="{{ $tl['id'] }}" data-nama="{{ $tl['nama'] }}">
                                        <span class="text-sm text-slate-700">{{ $tl['nama'] }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Filter Kategori Gaji --}}
                    <div class="flex flex-col">
                        
                        <select id="filterKategori" class="border border-[#E5E7EB] rounded-lg px-3 py-1.5 text-sm bg-[#F8FAFC] focus:outline-none focus:ring-2 focus:ring-slate-300 w-52">
                            <option value="">Semua Kategori</option>
                            <option value="borongan cabut">Borongan Cabut</option>
                            <option value="borongan cetak">Borongan Cetak</option>
                            <option value="harian">Harian</option>
                            <option value="bulanan">Bulanan</option>
                        </select>
                    </div>

                </div>
                <hr class="my-5 border-[#E5E7EB]">

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Pilih Karyawan</span>
                            <span id="selectedCount" class="bg-[#F8FAFC] text-slate-600 text-xs px-2 py-0.5 rounded-full">0 dipilih</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" id="btnSelectAll" onclick="toggleSelectAllVisible()"
                                class="text-xs px-3 py-1.5 rounded-lg border border-[#E5E7EB] hover:bg-[#F8FAFC] text-slate-500 transition">
                                Pilih Semua
                            </button>

                            <div class="w-px h-5 bg-[#E5E7EB] mx-1"></div>

                            <button type="button" id="btnKeterangan" onclick="openNoteModal()" disabled
                                class="text-xs px-3 py-1.5 rounded-lg border border-[#E5E7EB] text-slate-400 cursor-not-allowed transition">
                                + Tambah Keterangan
                            </button>
                            <button type="submit"
                                class="text-xs px-3 py-1.5 rounded-lg bg-[#4F46E5] text-white hover:bg-[#4338CA] transition">
                                Lihat Detail Absensi
                            </button>
                        </div>
                    </div>

                    <input id="searchKaryawan" type="text" placeholder="Cari nama karyawan..."
                        class="w-full bg-[#F8FAFC] border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm text-slate-700 mb-2" />

                    <div class="overflow-auto max-h-[55vh] border border-[#E5E7EB] rounded-lg" id="karyawanList">
                        <style>
                            .karyawan-item:hover { background-color: #f0fdf4 !important; }
                            .karyawan-item:hover td { background-color: #f0fdf4 !important; }
                        </style>
                        <table class="w-full text-xs whitespace-nowrap min-w-[1400px]">
                            <thead class="sticky top-0 bg-[#F8FAFC] z-10 text-[11px] font-medium text-slate-400 uppercase tracking-wide">
                                <tr>
                                    <th class="px-2 py-2 sticky left-0 bg-[#F8FAFC] z-20 border-r border-[#E5E7EB]">
                                        <input type="checkbox" id="checkAllKaryawan" class="accent-[#4F46E5]"
                                            onclick="document.querySelectorAll('.karyawan-check:not([style*=\'display: none\'])').forEach(c=>c.checked=this.checked); updateSelectedCount();">
                                    </th>
                                    <th class="px-2 py-2 sticky left-10 bg-[#F8FAFC] z-20 border-r border-[#E5E7EB]">PIN</th>
                                    <th class="px-2 py-2 sticky left-20 bg-[#F8FAFC] z-20 border-r border-[#E5E7EB] min-w-[160px]">Nama</th>
                                    <th class="px-2 py-2 sticky left-[264px] bg-[#F8FAFC] z-20 border-r border-[#E5E7EB] min-w-[140px]">NIP</th>
                                    <th class="px-2 py-2 text-left">NIK</th>
                                    <th class="px-2 py-2 text-left">L/P</th>
                                    <th class="px-2 py-2 text-left">Jabatan</th>
                                    <th class="px-2 py-2 text-left">Level</th>
                                    <th class="px-2 py-2 text-left">Bagian</th>
                                    <th class="px-2 py-2 text-left">Kategori Gaji</th>
                                    <th class="px-2 py-2 text-left">Departemen</th>
                                    <th class="px-2 py-2 text-left">TL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($karyawan as $k)
                                @php
                                    $tlName = $tlMap[$k->tl_id] ?? '-';
                                @endphp
                                <tr class="karyawan-item border-t border-[#E5E7EB] cursor-pointer transition-colors duration-100"
                                    data-bagian="{{ $k->bagian }}"
                                    data-kategori="{{ $k->kategori_gaji }}"
                                    data-tl-id="{{ $k->tl_id }}"
                                    onclick="this.querySelector('.karyawan-check').click()">
                                    <td class="px-2 py-1.5 sticky left-0 bg-white z-10 border-r border-[#E5E7EB]" onclick="event.stopPropagation()">
                                        <input type="checkbox" 
                                            name="selected_users[]" 
                                            value="{{ $k->pin }}"
                                            class="karyawan-check accent-[#4F46E5]"
                                            onchange="updateSelectedCount()">
                                    </td>
                                    <td class="px-2 py-1.5 sticky left-10 bg-white z-10 border-r border-[#E5E7EB] font-mono text-slate-400 text-xs">{{ $k->pin }}</td>
                                    <td class="px-2 py-1.5 sticky left-20 bg-white z-10 border-r border-[#E5E7EB] min-w-[160px] font-medium text-slate-800">{{ $k->nama }}</td>
                                    <td class="px-2 py-1.5 sticky left-[264px] bg-white z-10 border-r border-[#E5E7EB] text-slate-600">{{ $k->nip ?: '-' }}</td>
                                    <td class="px-2 py-1.5 text-slate-600 font-mono text-xs">{{ $k->nik ?: '-' }}</td>
                                    <td class="px-2 py-1.5 text-center">{{ $k->jk ?: '-' }}</td>
                                    <td class="px-2 py-1.5">{{ $k->job_title ?: '-' }}</td>
                                    <td class="px-2 py-1.5">{{ $k->job_level ?: '-' }}</td>
                                    <td class="px-2 py-1.5">{{ $k->bagian ?: '-' }}</td>
                                    <td class="px-2 py-1.5">{{ $k->kategori_gaji ?: '-' }}</td>
                                    <td class="px-2 py-1.5">{{ $k->departemen ?: '-' }}</td>
                                    <td class="px-2 py-1.5">{{ $tlName }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>

        <div id="noteModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="font-semibold text-slate-800">Tambah Keterangan Absensi (Bulk)</h3>
                    <button type="button" onclick="closeNoteModal()" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
                </div>

                <form method="POST" action="{{ route('absensi.notes.bulk') }}">
                    @csrf
                    <div id="selectedPinsContainer"></div>
                    <div id="selectedPinsSummary" class="mb-4 text-sm text-slate-500">Pilih karyawan terlebih dahulu sebelum menambahkan keterangan.</div>

                    <div class="mb-4">
                        <label class="text-sm font-medium text-slate-600 block mb-1">Tanggal Dari</label>
                        <input type="date" name="tanggal_dari" id="noteFrom"
                            value="{{ date('Y-m-d') }}"
                            required
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                    </div>

                    <div class="mb-4">
                        <label class="text-sm font-medium text-slate-600 block mb-1">Tanggal Sampai</label>
                        <input type="date" name="tanggal_sampai" id="noteTo"
                            value="{{ date('Y-m-d') }}"
                            required
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                    </div>

                    <div class="mb-6">
                        <label class="text-sm font-medium text-slate-600 block mb-1">Kode</label>
                        <select name="code" id="noteCode"
                            required
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                            <option value="">Pilih kode...</option>
                            <option value="S">S - Sakit</option>
                            <option value="I">I - Izin</option>
                            <option value="A">A - Alfa</option>
                            <option value="SSD">SSD - Sakit Surat Dokter</option>
                            <option value="Cuti">Cuti</option>
                            <option value="GL">GL - Ganti Libur</option>
                            <option value="Dll">Dll - Lainnya</option>
                        </select>
                    </div>

                    <div class="mb-6" id="noteReasonGroup">
                        <label class="text-sm font-medium text-slate-600 block mb-1">Keterangan</label>
                        <textarea name="note" id="noteReason" rows="3" readonly placeholder="Pilih kode untuk melihat atau mengisi keterangan"
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300"></textarea>
                        <p class="text-xs text-slate-400 mt-2" id="noteHelperText">Pilih kode untuk menambahkan keterangan.</p>
                    </div>

                    <div class="flex gap-3 justify-end">
                        <button type="button" onclick="closeNoteModal()"
                            class="border border-[#E5E7EB] text-slate-600 px-4 py-2 rounded-lg text-sm hover:bg-[#F8FAFC]">
                            Batal
                        </button>
                        <button type="submit"
                            class="bg-[#4F46E5] text-white px-4 py-2 rounded-lg text-sm hover:bg-[#4338CA]">
                            Simpan Keterangan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function setRange(type) {
            const today = new Date();
            let dari, sampai;

            if (type === 'today') {
                dari = sampai = formatDate(today);
            } else if (type === 'yesterday') {
                const y = new Date(today);
                y.setDate(today.getDate() - 1);
                dari = sampai = formatDate(y);
            } else if (type === 'this_month') {
                dari = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
                sampai = formatDate(today);
            } else if (type === 'last_month') {
                dari = formatDate(new Date(today.getFullYear(), today.getMonth() - 1, 1));
                sampai = formatDate(new Date(today.getFullYear(), today.getMonth(), 0));
            }

            document.getElementById('tanggal_dari').value = dari;
            document.getElementById('tanggal_sampai').value = sampai;

            ['btn_today', 'btn_yesterday', 'btn_this_month', 'btn_last_month'].forEach(function(id) {
                document.getElementById(id).className = 'text-xs px-3 py-1.5 rounded-full border border-[#E5E7EB] text-slate-500 hover:border-slate-400 hover:text-slate-700 transition';
            });

            const activeMap = {
                'today': 'btn_today',
                'yesterday': 'btn_yesterday',
                'this_month': 'btn_this_month',
                'last_month': 'btn_last_month'
            };
            document.getElementById(activeMap[type]).className = 'text-xs px-3 py-1.5 rounded-full border border-[#4F46E5] bg-[#4F46E5] text-white transition';
        }

        function setActiveShortcut(button) {
            document.querySelectorAll('.shortcut-button').forEach(function(btn) {
                btn.classList.remove('border-[#4F46E5]', 'bg-[#4F46E5]', 'text-white');
                btn.classList.add('border-[#E5E7EB]', 'text-slate-500');
            });

            if (button) {
                button.classList.add('border-[#4F46E5]', 'bg-[#4F46E5]', 'text-white');
                button.classList.remove('border-[#E5E7EB]', 'text-slate-500');
            }
        }

        function formatDate(d) {
            return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        }

        function updateSelectedCount() {
            const visibleCheckboxes = Array.from(document.querySelectorAll('.karyawan-item:not([style*=\'display: none\']) .karyawan-check'));
            const selectedCheckboxes = document.querySelectorAll('.karyawan-check:checked');
            const count = selectedCheckboxes.length;
            document.getElementById('selectedCount').textContent = count + ' dipilih';

            const btn = document.getElementById('btnKeterangan');
            if (count > 0) {
                btn.disabled = false;
                btn.className = 'text-xs px-3 py-1.5 rounded-lg border border-[#4F46E5] text-[#4F46E5] hover:bg-[#EEF2FF] transition cursor-pointer';
            } else {
                btn.disabled = true;
                btn.className = 'text-xs px-3 py-1.5 rounded-lg border border-[#E5E7EB] text-slate-400 cursor-not-allowed transition';
            }

            const selectAllBtn = document.getElementById('btnSelectAll');
            if (visibleCheckboxes.length > 0 && visibleCheckboxes.every(c => c.checked)) {
                selectAllBtn.className = 'text-xs px-3 py-1.5 rounded-lg border border-green-300 bg-green-100 text-green-700 hover:bg-green-200 transition';
            } else {
                selectAllBtn.className = 'text-xs px-3 py-1.5 rounded-lg border border-[#E5E7EB] text-slate-500 hover:bg-[#F8FAFC] transition';
            }
        }

        function toggleSelectAllVisible() {
            const visibleCheckboxes = Array.from(document.querySelectorAll('.karyawan-item:not([style*=\'display: none\']) .karyawan-check'));
            const allSelected = visibleCheckboxes.length > 0 && visibleCheckboxes.every(c => c.checked);
            visibleCheckboxes.forEach(c => c.checked = !allSelected);
            updateSelectedCount();
        }

        function openNoteModal() {
            const checked = document.querySelectorAll('.karyawan-check:checked');
            if (checked.length === 0) {
                alert('Pilih karyawan terlebih dahulu sebelum menambahkan keterangan.');
                return;
            }

            const pins = Array.from(checked).map(c => c.value);
            const container = document.getElementById('selectedPinsContainer');
            container.innerHTML = '';
            pins.forEach(function(pin) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_pins[]';
                input.value = pin;
                container.appendChild(input);
            });

            const summary = document.getElementById('selectedPinsSummary');
            if (summary) {
                summary.textContent = 'Menambahkan keterangan untuk ' + pins.length + ' karyawan: ' + pins.join(', ');
            }

            document.getElementById('noteModal').classList.remove('hidden');
        }

        function closeNoteModal() {
            document.getElementById('noteModal').classList.add('hidden');
        }

        function toggleNoteReason() {
            const code = document.getElementById('noteCode')?.value;
            const reasonInput = document.getElementById('noteReason');
            const helperText = document.getElementById('noteHelperText');
            const defaultNoteText = {
                'S': 'Sakit',
                'I': 'Izin',
                'A': 'Alpha',
                'SSD': 'Sakit Surat Dokter',
                'Cuti': 'Cuti',
                'GL': 'Ganti Libur',
            };

            if (!reasonInput || !helperText) {
                return;
            }

            if (code === 'Dll') {
                reasonInput.value = '';
                reasonInput.required = true;
                reasonInput.readOnly = false;
                reasonInput.placeholder = 'Tuliskan alasan / detail untuk kode Dll';
                helperText.textContent = 'Wajib diisi jika memilih kode Dll.';
            } else if (defaultNoteText.hasOwnProperty(code)) {
                reasonInput.value = defaultNoteText[code];
                reasonInput.required = false;
                reasonInput.readOnly = true;
                reasonInput.placeholder = 'Keterangan otomatis diisi';
                helperText.textContent = 'Keterangan otomatis diisi untuk kode ' + code + '.';
            } else {
                reasonInput.value = '';
                reasonInput.required = false;
                reasonInput.readOnly = true;
                reasonInput.placeholder = 'Pilih kode untuk melihat atau mengisi keterangan';
                helperText.textContent = 'Pilih kode untuk menambahkan keterangan.';
            }
        }

        document.getElementById('noteModal').addEventListener('click', function(e) {
            if (e.target === this) closeNoteModal();
        });

        document.getElementById('noteCode')?.addEventListener('change', toggleNoteReason);
        toggleNoteReason();

        document.querySelectorAll('.karyawan-check').forEach(function(c) {
            c.addEventListener('change', updateSelectedCount);
        });

        let originalRows = [];

        document.addEventListener('DOMContentLoaded', function() {
            originalRows = Array.from(document.querySelectorAll('.karyawan-item'));
            updateSelectedCount();
        });

        document.getElementById('searchKaryawan')?.addEventListener('input', applyAllFilters);

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
                originalRows.forEach(row => row.style.display = '');
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
        document.getElementById('tlDropdownMenu')?.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    </script>
@endsection
