@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between gap-4">
    <div>
        <h1 class="text-xl font-semibold text-slate-800">Sinkron Karyawan dari Mesin</h1>
    </div>
    <div>
        <a href="{{ route('karyawan.index') }}" class="pbtn pbtn-secondary">
            <span class="pbtn-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            </span>
            <span>Kembali</span>
        </a>
    </div>
</div>

<div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-700">
    Ditemukan {{ count($newUsers) }} karyawan baru.
</div>

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">PIN</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama dari Mesin</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse ($newUsers as $pin => $nama)
                <tr>
                    <td class="px-4 py-3 text-sm font-mono text-slate-600">{{ $pin }}</td>
                    <td class="px-4 py-3 text-sm text-slate-800 uppercase">{{ $nama }}</td>
                    <td class="px-4 py-3">
                        <button type="button" onclick="openModal('{{ $pin }}', '{{ addslashes($nama) }}')" class="pbtn pbtn-primary pbtn-sm">
                            Simpan
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-4 py-8 text-center text-sm text-slate-500" colspan="3">
                        Tidak ada karyawan baru untuk disinkronkan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div id="syncModal" class="hidden bg-black/50 fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <div id="modal_user_info" class="text-sm font-semibold text-slate-700">User -</div>
            </div>
            <button type="button" onclick="closeModal()" class="text-slate-500 hover:text-slate-900">✕</button>
        </div>

        <form action="{{ route('karyawan.store') }}" method="POST" class="mt-6">
            @csrf
            <input type="hidden" name="pin" id="modal_pin" value="" />

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">Nama Lengkap *</label>
                    <input id="modal_nama" name="nama" type="text" class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition" required />
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">NIP</label>
                    <input name="nip" type="text" placeholder="Masukkan NIP" class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition" />
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">NIK</label>
                    <input name="nik" type="text" placeholder="Masukkan NIK" class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition" />
                </div>
                <div>
                {{-- tempat_lahir --}}
                <div>
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">Tempat Lahir</label>
                    <input name="tempat_lahir" type="text" placeholder="Masukkan tempat lahir"
                        class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition" />
                </div>
                {{-- tanggal_lahir --}}
                <div>
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">Tanggal Lahir</label>
                    <input name="tanggal_lahir" type="date"
                        class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition" />
                </div>
                {{-- alamat --}}
                <div>
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">Alamat</label>
                    <textarea name="alamat" rows="2" placeholder="Masukkan alamat"
                        class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition"></textarea>
                </div>
                {{-- no_hp --}}
                <div>
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">No. HP</label>
                    <input name="no_hp" type="text" placeholder="Masukkan nomor HP"
                        class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition" />
                </div>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">Jenis Kelamin</label>
                    <select name="jk" class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
                        <option value="">- Pilih -</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">Jabatan</label>
                    <select name="job_title" class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
                        <option value="">- Pilih -</option>
                        <option value="TL Cuci">TL Cuci</option>
                        <option value="TL Cabut">TL Cabut</option>
                        <option value="TL Kedatangan">TL Kedatangan</option>
                        <option value="Operator">Operator</option>
                        <option value="SPV Moulding">SPV Moulding</option>
                        <option value="TL Moulding">TL Moulding</option>
                        <option value="GTL Moulding">GTL Moulding</option>
                        <option value="GTL Cabut">GTL Cabut</option>
                        <option value="Driver">Driver</option>
                        <option value="Manager Produksi">Manager Produksi</option>
                        <option value="SPV Kedatangan">SPV Kedatangan</option>
                        <option value="Checker Cabut">Checker Cabut</option>
                        <option value="Admin Produktivitas">Admin Produktivitas</option>
                        <option value="Checker Moulding">Checker Moulding</option>
                        <option value="TL Pengiriman">TL Pengiriman</option>
                        <option value="Admin">Admin</option>
                        <option value="TL Packing">TL Packing</option>
                        <option value="Superintenden">Superintenden</option>
                        <option value="Ass. Superintenden">Ass. Superintenden</option>
                        <option value="TL Cutter &amp; Flek">TL Cutter &amp; Flek</option>
                        <option value="SPV Packing">SPV Packing</option>
                        <option value="Security">Security</option>
                        <option value="Sanitasi">Sanitasi</option>
                        <option value="Purchasing/ Logistic">Purchasing/ Logistic</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Finance Accounting">Finance Accounting</option>
                        <option value="General Affair">General Affair</option>
                        <option value="HRD">HRD</option>
                        <option value="Payroll">Payroll</option>
                        <option value="GTL Packing">GTL Packing</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">Level Jabatan</label>
                    <select name="job_level" class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
                        <option value="">- Pilih -</option>
                        <option value="Operator">Operator</option>
                        <option value="Team Leader">Team Leader</option>
                        <option value="Supervisor">Supervisor</option>
                        <option value="Group Team Leader">Group Team Leader</option>
                        <option value="Manager">Manager</option>
                        <option value="Checker">Checker</option>
                        <option value="Administrasi">Administrasi</option>
                        <option value="Driver">Driver</option>
                        <option value="Superintenden">Superintenden</option>
                        <option value="General Manager">General Manager</option>
                        <option value="Security">Security</option>
                        <option value="Sanitasi">Sanitasi</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Finance Accounting">Finance Accounting</option>
                        <option value="General Affair">General Affair</option>
                        <option value="HRD">HRD</option>
                        <option value="Payroll">Payroll</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">Bagian</label>
                    <select name="bagian" class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
                        <option value="">- Pilih -</option>
                        <option value="-">-</option>
                        <option value="Manager Produksi">Manager Produksi</option>
                        <option value="Bahan Baku">Bahan Baku</option>
                        <option value="Cabut">Cabut</option>
                        <option value="Dry A">Dry A</option>
                        <option value="Moulding">Moulding</option>
                        <option value="Cuci Bersih">Cuci Bersih</option>
                        <option value="Cuci Kotor">Cuci Kotor</option>
                        <option value="Admin">Admin</option>
                        <option value="Rambang">Rambang</option>
                        <option value="Cutter &amp; Flek">Cutter &amp; Flek</option>
                        <option value="Dry B &amp; HCR">Dry B &amp; HCR</option>
                        <option value="HCR Moulding">HCR Moulding</option>
                        <option value="Admin Cabut &amp; Bahan Baku">Admin Cabut &amp; Bahan Baku</option>
                        <option value="Packing">Packing</option>
                        <option value="Admin Drying &amp; Moulding">Admin Drying &amp; Moulding</option>
                        <option value="SPV">SPV</option>
                        <option value="TL Pre Cleaning">TL Pre Cleaning</option>
                        <option value="Checker Moulding">Checker Moulding</option>
                        <option value="Timbang Indomie">Timbang Indomie</option>
                        <option value="Administrasi">Administrasi</option>
                        <option value="Grading">Grading</option>
                        <option value="Final Grading">Final Grading</option>
                        <option value="Titil HCR">Titil HCR</option>
                        <option value="Moulding Indomie">Moulding Indomie</option>
                        <option value="CCP 1">CCP 1</option>
                        <option value="Prewash">Prewash</option>
                        <option value="Driver">Driver</option>
                        <option value="Admin Packing">Admin Packing</option>
                        <option value="Admin Cabut">Admin Cabut</option>
                        <option value="Security">Security</option>
                        <option value="Sanitasi">Sanitasi</option>
                        <option value="Kasir Perusahaan">Kasir Perusahaan</option>
                        <option value="Maintenance IT">Maintenance IT</option>
                        <option value="Finance Accounting">Finance Accounting</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Borongan">Borongan</option>
                        <option value="Bulanan">Bulanan</option>
                        <option value="Harian">Harian</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">Departemen</label>
                    <select name="departemen" class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
                        <option value="">- Pilih -</option>
                        <option value="Produksi">Produksi</option>
                        <option value="Support">Support</option>
                        <option value="Operation">Operation</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">TL (Team Leader)</label>
                    <div class="tl-combobox" style="position:relative;">
                        <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">TL (Team Leader)</label>
                        <input type="text" id="sync_tl_search" placeholder="Ketik untuk cari TL..."
                            autocomplete="off"
                            class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition"
                            onfocus="showTLDropdown('sync')" onkeyup="filterTLDropdown('sync')" />
                        <input type="hidden" name="tl_id" id="sync_tl_id" value="" />
                        <div id="sync_tl_dropdown" class="hidden absolute z-40 w-full bg-white border border-[#C8D9E6] rounded-lg shadow-lg max-h-48 overflow-y-auto mt-1"
                            style="display:none;">
                            <div class="p-2 text-xs text-slate-400 border-b border-[#E5E7EB] cursor-pointer hover:bg-[#F8FAFC]"
                                onclick="selectTL('sync', '', '')">— Tidak Ada —</div>
                            <!-- CABUT -->
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide px-3 py-1 bg-[#F8FAFC]">CABUT</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="8" onclick="selectTL('sync', '8', 'Karyawati')">Karyawati</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="3" onclick="selectTL('sync', '3', 'Sri Utami')">Sri Utami</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="2" onclick="selectTL('sync', '2', 'ST Nur Farokah')">ST Nur Farokah</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="25" onclick="selectTL('sync', '25', 'Fhilis Sulestari')">Fhilis Sulestari</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="22" onclick="selectTL('sync', '22', 'Muhammad Regatana Hidayatulloh')">Muhammad Regatana Hidayatulloh</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="119" onclick="selectTL('sync', '119', 'Zusita Arsdhia Indrayani')">Zusita Arsdhia Indrayani</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="34" onclick="selectTL('sync', '34', 'Wahyu Surodo')">Wahyu Surodo</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="30" onclick="selectTL('sync', '30', 'Deniko Fergian')">Deniko Fergian</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="109" onclick="selectTL('sync', '109', 'Ruliatul Fidiah')">Ruliatul Fidiah</div>
                            <!-- CETAK -->
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide px-3 py-1 bg-[#F8FAFC]">CETAK</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="57" onclick="selectTL('sync', '57', 'Muhammad Tamamur Ridlwan')">Muhammad Tamamur Ridlwan</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="7" onclick="selectTL('sync', '7', 'Anita')">Anita</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="74" onclick="selectTL('sync', '74', 'Nur Alim Zainuri')">Nur Alim Zainuri</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="27" onclick="selectTL('sync', '27', \"Anas Ja'far\")">Anas Ja'far</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="48" onclick="selectTL('sync', '48', 'M.Jamaludin')">M.Jamaludin</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="134" onclick="selectTL('sync', '134', 'M. Jamaluddin Saputra')">M. Jamaluddin Saputra</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="99" onclick="selectTL('sync', '99', 'Nila Widya Sari')">Nila Widya Sari</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="113" onclick="selectTL('sync', '113', 'Nurul Izzuddin')">Nurul Izzuddin</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="75" onclick="selectTL('sync', '75', 'Niko Yudho')">Niko Yudho</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="71" onclick="selectTL('sync', '71', 'Tsalis Akmaludin')">Tsalis Akmaludin</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="69" onclick="selectTL('sync', '69', 'Prayoga Dwi Cahyo')">Prayoga Dwi Cahyo</div>
                            <!-- DAN LAIN LAIN -->
                            <div class="text-[10px] font-semibold text-slate-400 uppercase tracking-wide px-3 py-1 bg-[#F8FAFC]">DAN LAIN LAIN</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="1" onclick="selectTL('sync', '1', 'Anik')">Anik</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="98" onclick="selectTL('sync', '98', 'M Gaung Sidiq')">M Gaung Sidiq</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="40" onclick="selectTL('sync', '40', 'Cankiswan')">Cankiswan</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="118" onclick="selectTL('sync', '118', 'Kerinna')">Kerinna</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="865" onclick="selectTL('sync', '865', 'TL CCP 1')">TL CCP 1</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="871" onclick="selectTL('sync', '871', 'Sanitasi')">Sanitasi</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="872" onclick="selectTL('sync', '872', 'Checker')">Checker</div>
                            <div class="tl-option px-3 py-1.5 text-sm cursor-pointer hover:bg-[#EEF4FF] text-slate-700" data-id="43" onclick="selectTL('sync', '43', 'GD Kart')">GD Kart</div>
                        </div>
                    </div>
                </div>
                <div class="col-span-3">
                    <label class="block text-[11px] font-medium text-[#567C8D] mb-1 uppercase tracking-wide">Kategori Gaji</label>
                    <select name="kategori_gaji" class="w-full px-3 py-2 text-[13px] text-[#2F4156] bg-white border border-[#C8D9E6] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#567C8D]/30 focus:border-[#567C8D] placeholder-[#8BAFC4] transition">
                        <option value="">- Pilih -</option>
                        <option value="borongan cabut">borongan cabut</option>
                        <option value="borongan cetak">borongan cetak</option>
                        <option value="harian">harian</option>
                        <option value="bulanan">bulanan</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal()" class="pbtn pbtn-secondary">Batal</button>
                <button type="submit" class="pbtn pbtn-primary">Simpan Karyawan</button>
            </div>
        </form>
    </div>
</div>

<script>
        function openModal(pin, nama) {
            document.getElementById('modal_pin').value = pin;
            document.getElementById('modal_nama').value = nama;
            document.getElementById('modal_user_info').textContent = 'PIN: ' + pin + ' - Nama: ' + nama;
            document.getElementById('syncModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('syncModal').classList.add('hidden');
            document.getElementById('modal_nama').value = '';
            document.getElementById('modal_pin').value = '';
        }

        document.getElementById('syncModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // ===== TL SEARCHABLE COMBOBOX =====
    function showTLDropdown(prefix) {
        const dd = document.getElementById(prefix + '_tl_dropdown');
        dd.style.display = 'block';
    }

    function hideTLDropdown(prefix) {
        setTimeout(() => {
            document.getElementById(prefix + '_tl_dropdown').style.display = 'none';
        }, 200);
    }

    function filterTLDropdown(prefix) {
        const input = document.getElementById(prefix + '_tl_search');
        const q = input.value.toLowerCase().trim();
        const dd = document.getElementById(prefix + '_tl_dropdown');
        const options = dd.querySelectorAll('.tl-option');
        let visibleCount = 0;
        
        // Also show the "Tidak Ada" option
        const firstChild = dd.firstElementChild;
        if (firstChild) firstChild.style.display = 'block';
        
        options.forEach(opt => {
            const text = opt.textContent.toLowerCase();
            if (!q || text.includes(q)) {
                opt.style.display = 'block';
                visibleCount++;
            } else {
                opt.style.display = 'none';
            }
        });
        
        // Hide group headers if no visible children
        dd.querySelectorAll('.text-\\[10px\\]').forEach(hdr => {
            let next = hdr.nextElementSibling;
            let hasVisible = false;
            while (next && next.classList.contains('tl-option')) {
                if (next.style.display !== 'none') { hasVisible = true; break; }
                next = next.nextElementSibling;
            }
            hdr.style.display = hasVisible ? 'block' : 'none';
        });
        
        dd.style.display = 'block';
    }

    function selectTL(prefix, id, nama) {
        document.getElementById(prefix + '_tl_id').value = id;
        document.getElementById(prefix + '_tl_search').value = nama;
        document.getElementById(prefix + '_tl_dropdown').style.display = 'none';
    }

    // Click outside to close TL dropdown
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.tl-combobox').forEach(function(container) {
            if (!container.contains(e.target)) {
                const dd = container.querySelector('[id$="_tl_dropdown"]');
                if (dd) dd.style.display = 'none';
            }
        });
    });
</script>
@endsection
