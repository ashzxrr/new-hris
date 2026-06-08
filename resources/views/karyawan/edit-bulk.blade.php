@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Edit Karyawan</h1>
            <p class="text-sm text-slate-400">{{ count($karyawan) }} karyawan dipilih</p>
        </div>
        <a href="{{ route('karyawan.index') }}"
            class="text-xs px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">
            ← Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('karyawan.updateBulk') }}">
        @csrf
        @method('PUT')

        @foreach($groupedKaryawan as $tlId => $group)
        <div class="mb-6">
            <div class="mb-3 px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">
                            {{ $tlId === 'none' ? 'Tanpa TL' : ($tlMap[$tlId] ?? 'TL #' . $tlId) }}
                        </p>
                        <p class="text-xs text-slate-500">{{ $group->count() }} karyawan</p>
                    </div>
                </div>
            </div>

            @foreach($group as $k)
            <div class="bg-white rounded-xl border border-slate-200 p-6 mb-4">
                <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100">
                    <span class="text-xs font-mono bg-slate-100 text-slate-500 px-2 py-1 rounded">PIN: {{ $k->pin }}</span>
                    <span class="font-semibold text-slate-800">{{ $k->nama }}</span>
                    <span class="text-xs text-slate-400">ID: {{ $k->id }}</span>
                </div>

            <input type="hidden" name="karyawan[{{ $k->id }}][id]" value="{{ $k->id }}">

            <div class="grid grid-cols-3 gap-4">
                <div class="col-span-2">
                    <label class="text-xs text-slate-500 mb-1 block">Nama Lengkap *</label>
                    <input type="text" name="karyawan[{{ $k->id }}][nama]" value="{{ $k->nama }}" required
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Jenis Kelamin</label>
                    <select name="karyawan[{{ $k->id }}][jk]"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Pilih -</option>
                        <option value="L" {{ $k->jk === 'L' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="P" {{ $k->jk === 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">NIP</label>
                    <input type="text" name="karyawan[{{ $k->id }}][nip]" value="{{ $k->nip }}"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">NIK</label>
                    <input type="text" name="karyawan[{{ $k->id }}][nik]" value="{{ $k->nik }}"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Jabatan</label>
                    <select name="karyawan[{{ $k->id }}][job_title]"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Pilih -</option>
                        @foreach(['TL Cuci','TL Cabut','TL Kedatangan','Operator','SPV Moulding','TL Moulding','GTL Moulding','GTL Cabut','Driver','Manager Produksi','SPV Kedatangan','Checker Cabut','Admin Produktivitas','Checker Moulding','TL Pengiriman','Admin','TL Packing','Superintenden','Ass. Superintenden','TL Cutter & Flek','SPV Packing','Security','Sanitasi','Purchasing/ Logistic','Maintenance','Finance Accounting','General Affair','HRD','Payroll','GTL Packing'] as $jt)
                        <option value="{{ $jt }}" {{ $k->job_title === $jt ? 'selected' : '' }}>{{ $jt }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Level Jabatan</label>
                    <select name="karyawan[{{ $k->id }}][job_level]"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Pilih -</option>
                        @foreach(['Operator','Team Leader','Supervisor','Group Team Leader','Manager','Checker','Administrasi','Driver','Superintenden','General Manager','Security','Sanitasi','Maintenance','Finance Accounting','General Affair','HRD','Payroll'] as $jl)
                        <option value="{{ $jl }}" {{ $k->job_level === $jl ? 'selected' : '' }}>{{ $jl }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Bagian</label>
                    <select name="karyawan[{{ $k->id }}][bagian]"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Pilih -</option>
                        @foreach(['-','Manager Produksi','Bahan Baku','Cabut','Dry A','Moulding','Cuci Bersih','Cuci Kotor','Admin','Rambang','Cutter & Flek','Dry B & HCR','HCR Moulding','Admin Cabut & Bahan Baku','Packing','Admin Drying & Moulding','SPV','TL Pre Cleaning','Checker Moulding','Timbang Indomie','Administrasi','Grading','Final Grading','Titil HCR','Moulding Indomie','CCP 1','Prewash','Driver','Admin Packing','Admin Cabut','Security','Sanitasi','Kasir Perusahaan','Maintenance IT','Finance Accounting','Maintenance','Borongan','Bulanan','Harian'] as $bg)
                        <option value="{{ $bg }}" {{ $k->bagian === $bg ? 'selected' : '' }}>{{ $bg }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Departemen</label>
                    <select name="karyawan[{{ $k->id }}][departemen]"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Pilih -</option>
                        @foreach(['Produksi','Support','Operation'] as $dep)
                        <option value="{{ $dep }}" {{ $k->departemen === $dep ? 'selected' : '' }}>{{ $dep }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-xs text-slate-500 mb-1 block">Kategori Gaji</label>
                    <select name="karyawan[{{ $k->id }}][kategori_gaji]"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Pilih -</option>
                        @foreach(['borongan cabut','borongan cetak','harian','bulanan'] as $kg)
                        <option value="{{ $kg }}" {{ $k->kategori_gaji === $kg ? 'selected' : '' }}>{{ $kg }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-3">
                    <label class="text-xs text-slate-500 mb-1 block">TL (Team Leader)</label>
                    <select name="karyawan[{{ $k->id }}][tl_id]"
                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300">
                        <option value="">- Tidak Ada -</option>
                        <optgroup label="CABUT">
                            @foreach([['id'=>8,'nama'=>'Karyawati'],['id'=>3,'nama'=>'Sri Utami'],['id'=>2,'nama'=>'ST Nur Farokah'],['id'=>25,'nama'=>'Fhilis Sulestari'],['id'=>22,'nama'=>'Muhammad Regatana Hidayatulloh'],['id'=>119,'nama'=>'Zusita Arsdhia Indrayani'],['id'=>34,'nama'=>'Wahyu Surodo'],['id'=>30,'nama'=>'Deniko Fergian'],['id'=>109,'nama'=>'Ruliatul Fidiah']] as $tl)
                            <option value="{{ $tl['id'] }}" {{ $k->tl_id == $tl['id'] ? 'selected' : '' }}>{{ $tl['nama'] }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="CETAK">
                            @foreach([['id'=>57,'nama'=>'Muhammad Tamamur Ridlwan'],['id'=>7,'nama'=>'Anita'],['id'=>74,'nama'=>'Nur Alim Zainuri'],['id'=>27,'nama'=>"Anas Ja'far"],['id'=>48,'nama'=>'M.Jamaludin'],['id'=>99,'nama'=>'Nila Widya Sari'],['id'=>113,'nama'=>'Nurul Izzuddin'],['id'=>75,'nama'=>'Niko Yudho'],['id'=>71,'nama'=>'Tsalis Akmaludin'],['id'=>69,'nama'=>'Prayogo Dwi']] as $tl)
                            <option value="{{ $tl['id'] }}" {{ $k->tl_id == $tl['id'] ? 'selected' : '' }}>{{ $tl['nama'] }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="DAN LAIN LAIN">
                            @foreach([['id'=>1,'nama'=>'Anik'],['id'=>98,'nama'=>'M Gaung Sidiq'],['id'=>40,'nama'=>'Cankiswan'],['id'=>118,'nama'=>'Kerinna'],['id'=>63,'nama'=>'Puput Indarwati'],['id'=>865,'nama'=>'TL CCP 1'],['id'=>871,'nama'=>'Sanitasi'],['id'=>872,'nama'=>'Checker'],['id'=>43,'nama'=>'GD Kart']] as $tl)
                            <option value="{{ $tl['id'] }}" {{ $k->tl_id == $tl['id'] ? 'selected' : '' }}>{{ $tl['nama'] }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
            </div>
        </div>
            @endforeach
        </div>
        @endforeach

        <div class="sticky bottom-0 bg-white border-t border-slate-200 p-4 flex justify-between items-center">
            <span class="text-sm text-slate-400">{{ count($karyawan) }} karyawan akan diupdate</span>
            <div class="flex gap-3">
                <a href="{{ route('karyawan.index') }}"
                    class="border border-slate-200 text-slate-600 px-4 py-2 rounded-lg text-sm hover:bg-slate-50">
                    Batal
                </a>
                <button type="submit"
                    class="bg-slate-800 text-white px-6 py-2 rounded-lg text-sm hover:bg-slate-700">
                    Simpan Semua Perubahan
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
