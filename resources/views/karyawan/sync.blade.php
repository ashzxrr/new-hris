@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between gap-4">
    <div>
        <h1 class="text-xl font-semibold text-slate-800">Sinkron Karyawan dari Mesin</h1>
    </div>
    <div>
        <a href="{{ route('karyawan.index') }}" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
            Kembali
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
                        <button type="button" onclick="openModal('{{ $pin }}', '{{ addslashes($nama) }}')" class="inline-flex items-center rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-700 transition">
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
                    <label class="block text-sm font-medium text-slate-700">Nama Lengkap *</label>
                    <input id="modal_nama" name="nama" type="text" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">NIP</label>
                    <input name="nip" type="text" placeholder="Masukkan NIP" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">NIK</label>
                    <input name="nik" type="text" placeholder="Masukkan NIK" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Jenis Kelamin</label>
                    <select name="jk" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900">
                        <option value="">- Pilih -</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">Jabatan</label>
                    <select name="job_title" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900">
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
                    <label class="block text-sm font-medium text-slate-700">Level Jabatan</label>
                    <select name="job_level" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900">
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
                    <label class="block text-sm font-medium text-slate-700">Bagian</label>
                    <select name="bagian" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900">
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
                    <label class="block text-sm font-medium text-slate-700">Departemen</label>
                    <select name="departemen" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900">
                        <option value="">- Pilih -</option>
                        <option value="Produksi">Produksi</option>
                        <option value="Support">Support</option>
                        <option value="Operation">Operation</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700">TL (Team Leader)</label>
                    <select name="tl_id" class="mt-2 w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Tidak Ada -</option>
                        
                        <optgroup label="CABUT">
                            <option value="8">Karyawati</option>
                            <option value="3">Sri Utami</option>
                            <option value="2">ST Nur Farokah</option>
                            <option value="25">Fhilis Sulestari</option>
                            <option value="22">Muhammad Regatana Hidayatulloh</option>
                            <option value="119">Zusita Arsdhia Indrayani</option>
                            <option value="34">Wahyu Surodo</option>
                            <option value="30">Deniko Fergian</option>
                            <option value="109">Ruliatul Fidiah</option>
                        </optgroup>

                        <optgroup label="CETAK">
                            <option value="57">Muhammad Tamamur Ridlwan</option>

                            <option value="7">Anita</option>
                            <option value="74">Nur Alim Zainuri</option>
                            <option value="27">Anas Ja'far</option>
                            <option value="48">M.Jamaludin</option>
                            <option value="99">Nila Widya Sari</option>
                            <option value="113">Nurul Izzuddin</option>
                            <option value="75">Niko Yudho</option>
                            <option value="71">Tsalis Akmaludin</option>
                            <option value="69">Prayoga Dwi Cahyo</option>
                        </optgroup>

                        <optgroup label="DAN LAIN LAIN">
                            <option value="1">Anik</option>
                            <option value="98">M Gaung Sidiq</option>
                            <option value="40">Cankiswan</option>
                            <option value="118">Kerinna</option>
                            <option value="865">TL CCP 1</option>
                            <option value="871">Sanitasi</option>
                            <option value="872">Checker</option>
                            <option value="43">GD Kart</option>
                        </optgroup>
                    </select>
                </div>
                <div class="col-span-3">
                    <label class="block text-sm font-medium text-slate-700">Kategori Gaji</label>
                    <select name="kategori_gaji" class="mt-2 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900">
                        <option value="">- Pilih -</option>
                        <option value="borongan cabut">borongan cabut</option>
                        <option value="borongan cetak">borongan cetak</option>
                        <option value="harian">harian</option>
                        <option value="bulanan">bulanan</option>
                    </select>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <button type="button" onclick="closeModal()" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">Batal</button>
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 transition">Simpan Karyawan</button>
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
</script>
@endsection
