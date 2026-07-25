<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rows = [
            [
                'pin' => '250',
                'nip' => 'LMG-2026-947',
                'nama' => 'Rizki Sumantri',
                'nik' => null,
                'jk' => 'L',
                'job_title' => 'SECURITY',
                'job_level' => 'SECURITY',
                'bagian' => 'SECURITY',
                'kategori_gaji' => 'BULANAN',
            ],
            [
                'pin' => '352',
                'nip' => 'LMG-2026-949',
                'nama' => 'Wahyu Tri Afriyan',
                'nik' => null,
                'jk' => 'L',
                'job_title' => 'SECURITY',
                'job_level' => 'SECURITY',
                'bagian' => 'SECURITY',
                'kategori_gaji' => 'BULANAN',
            ],
            [
                'pin' => '365',
                'nip' => 'LMG-2026-950',
                'nama' => 'Permono Oky',
                'nik' => null,
                'jk' => 'L',
                'job_title' => 'DRIVER',
                'job_level' => 'DRIVER',
                'bagian' => 'DRIVER',
                'kategori_gaji' => 'BULANAN',
            ],
            [
                'pin' => '358',
                'nip' => 'LMG-2026-951',
                'nama' => 'Junita Shintya Rini',
                'nik' => null,
                'jk' => 'P',
                'job_title' => 'OPERATOR',
                'job_level' => 'OPERATOR',
                'bagian' => 'MOULDING',
                'kategori_gaji' => 'BORONGAN CETAK',
            ],
            [
                'pin' => '357',
                'nip' => 'LMG-2026-952',
                'nama' => 'Ratna Nurjanah',
                'nik' => null,
                'jk' => 'P',
                'job_title' => 'OPERATOR',
                'job_level' => 'OPERATOR',
                'bagian' => 'MOULDING',
                'kategori_gaji' => 'BORONGAN CETAK',
            ],
            [
                'pin' => '353',
                'nip' => 'LMG-2026-954',
                'nama' => 'Mega Putri Lestari',
                'nik' => null,
                'jk' => 'P',
                'job_title' => 'OPERATOR',
                'job_level' => 'OPERATOR',
                'bagian' => 'MOULDING',
                'kategori_gaji' => 'BORONGAN CETAK',
            ],
            [
                'pin' => '356',
                'nip' => 'LMG-2026-955',
                'nama' => 'Mar\'atus Sholiha',
                'nik' => null,
                'jk' => 'P',
                'job_title' => 'OPERATOR',
                'job_level' => 'OPERATOR',
                'bagian' => 'MOULDING',
                'kategori_gaji' => 'BORONGAN CETAK',
            ],
            [
                'pin' => '361',
                'nip' => 'LMG-2026-956',
                'nama' => 'As\'ad Ahmadi Nejad',
                'nik' => null,
                'jk' => 'L',
                'job_title' => 'OPERATOR',
                'job_level' => 'OPERATOR',
                'bagian' => 'MOULDING',
                'kategori_gaji' => 'BORONGAN CETAK',
            ],
            [
                'pin' => '362',
                'nip' => 'LMG-2026-957',
                'nama' => 'Muhammad Munir',
                'nik' => null,
                'jk' => 'L',
                'job_title' => 'OPERATOR',
                'job_level' => 'OPERATOR',
                'bagian' => 'MOULDING',
                'kategori_gaji' => 'BORONGAN CETAK',
            ],
            [
                'pin' => '369',
                'nip' => 'LMG-2026-958',
                'nama' => 'Zacky Febian Kusuma',
                'nik' => null,
                'jk' => 'L',
                'job_title' => 'OPERATOR',
                'job_level' => 'OPERATOR',
                'bagian' => 'MOULDING',
                'kategori_gaji' => 'BORONGAN CETAK',
            ],
            [
                'pin' => '367',
                'nip' => 'LMG-2026-959',
                'nama' => 'Valisa Sampurna Putri',
                'nik' => null,
                'jk' => 'P',
                'job_title' => 'OPERATOR',
                'job_level' => 'OPERATOR',
                'bagian' => 'MOULDING',
                'kategori_gaji' => 'BORONGAN CETAK',
            ],
            [
                'pin' => '370',
                'nip' => 'LMG-2026-960',
                'nama' => 'Ahmad Kiki Maulana',
                'nik' => null,
                'jk' => 'L',
                'job_title' => 'OPERATOR',
                'job_level' => 'OPERATOR',
                'bagian' => 'MOULDING',
                'kategori_gaji' => 'BORONGAN CETAK',
            ],
            [
                'pin' => '371',
                'nip' => 'LMG-2026-961',
                'nama' => 'Miftakhul Lutfi Maulidin',
                'nik' => null,
                'jk' => 'L',
                'job_title' => 'OPERATOR',
                'job_level' => 'OPERATOR',
                'bagian' => 'MOULDING',
                'kategori_gaji' => 'BORONGAN CETAK',
            ],
            [
                'pin' => '366',
                'nip' => 'LMG-2026-962',
                'nama' => 'Indah Ika Dwi Yulianti',
                'nik' => null,
                'jk' => 'P',
                'job_title' => 'OPERATOR',
                'job_level' => 'OPERATOR',
                'bagian' => 'MOULDING',
                'kategori_gaji' => 'BORONGAN CETAK',
            ],
            [
                'pin' => '368',
                'nip' => 'LMG-2026-963',
                'nama' => 'Marissa Eka Nur Rohmaniyah',
                'nik' => null,
                'jk' => 'P',
                'job_title' => 'OPERATOR',
                'job_level' => 'OPERATOR',
                'bagian' => 'MOULDING',
                'kategori_gaji' => 'BORONGAN CETAK',
            ],
        ];

        $includeTlColumn = Schema::hasColumn('users', 'tl_id');

        foreach ($rows as $row) {
            $data = [
                'pin' => $row['pin'],
                'nip' => $row['nip'],
                'nama' => $row['nama'],
                'nik' => $row['nik'],
                'jk' => $row['jk'],
                'job_title' => $row['job_title'],
                'job_level' => $row['job_level'],
                'bagian' => $row['bagian'],
                'kategori_gaji' => $row['kategori_gaji'],
            ];

            if ($includeTlColumn) {
                $data['tl_id'] = null;
            }

            DB::table('users')->insertOrIgnore($data);
        }

        // Unmatched names for manual review.
        // Excel without fingerprint match: Wawan Budi Santoso, Viva
        // Fingerprint without Excel match: Mseptian, Fifa, Andhikayuna, Msalmanhambali, Maulanaazzahfirmansyah, Desiwulandari, Ekabayu, UnnikMyani, Ferifathulmubin, Mainulyaqin, Erlanggasatriaagung
    }
}
