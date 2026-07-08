<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $kodeKaryawan = [
            'LMG-2020-111',
            'LMG-2017-032',
            'LMG-2023-777',
            'LMG-2025-363',
            'LMG-2025-617',
            'LMG-2026-870',
            'LMG-2022-360',
            'LMG-2015-008',
            'LMG-2025-064',
            'LMG-2025-213',
            'LMG-2025-472',
            'LMG-2025-282',
            'LMG-2025-837',
            'LMG-2026-912',
            'LMG-2026-918',
            'LMG-2018-053',
            'LMG-2020-082',
            'LMG-2018-061',
            'LMG-2023-658',
            'LMG-2025-179',
            'LMG-2021-126',
            'LMG-2022-389',
            'LMG-2024-1022',
            'LMG-2025-638',
            'LMG-2025-714',
            'LMG-2023-547',
            'LMG-2017-023',
            'LMG-2023-537',
            'LMG-2017-034',
            'LMG-2025-347',
            'LMG-2025-429',
            'LMG-2025-623',
            'LMG-2023-556',
            'LMG-2024-151',
            'LMG-2023-530',
            'LMG-2024-947',
            'LMG-2023-772',
            'LMG-2025-361',
            'LMG-2025-165',
            'LMG-2025-306',
            'LMG-2025-499',
            'LMG-2025-518',
            'LMG-2022-387',
            'LMG-2024-1009',
            'LMG-2025-303',
            'LMG-2025-304',
            'LMG-2025-662',
            'LMG-2025-744',
            'LMG-2025-743',
            'LMG-2025-745',
            'LMG-2025-784',
            'LMG-2024-153',
            'LMG-2025-136',
            'LMG-2025-675',
            'LMG-2025-742',
            'LMG-2025-747',
            'LMG-2025-795',
            'LMG-2026-882',
            'LMG-2024-229',
            'LMG-2025-169',
            'LMG-2025-738',
            'LMG-2025-746',
            'LMG-2025-526',
            'LMG-2024-308',
            'LMG-2025-157',
            'LMG-2025-446',
            'LMG-2025-520',
            'LMG-2025-208',
            'LMG-2025-285',
            'LMG-2025-515',
            'LMG-2025-357',
            'LMG-2025-393',
            'LMG-2025-450',
            'LMG-2025-451',
            'LMG-2025-457',
            'LMG-2025-366',
            'LMG-2025-542',
            'LMG-2025-579',
            'LMG-2025-192',
            'LMG-2025-519',
            'LMG-2025-476',
            'LMG-2025-582',
            'LMG-2025-489',
            'LMG-2025-521',
            'LMG-2025-475',
            'LMG-2025-492',
            'LMG-2025-473',
            'LMG-2025-474',
            'LMG-2025-549',
            'LMG-2025-209',
            'LMG-2025-210',
            'LMG-2025-284',
            'LMG-2025-358',
            'LMG-2025-362',
            'LMG-2025-456',
            'LMG-2025-158',
            'LMG-2025-527',
            'LMG-2025-444',
            'LMG-2025-455',
            'LMG-2025-496',
            'LMG-2024-327',
            'LMG-2025-435',
            'LMG-2025-495',
            'LMG-2025-553',
            'LMG-2025-630',
            'LMG-2025-156',
            'LMG-2025-541',
            'LMG-2025-524',
            'LMG-2025-500',
            'LMG-2025-494',
            'LMG-2025-626',
            'LMG-2025-459',
            'LMG-2025-543',
            'LMG-2025-556',
            'LMG-2025-629',
            'LMG-2025-212',
            'LMG-2025-458',
            'LMG-2025-426',
            'LMG-2025-478',
            'LMG-2025-554',
            'LMG-2025-517',
            'LMG-2025-442',
            'LMG-2025-490',
            'LMG-2025-497',
            'LMG-2025-525',
            'LMG-2024-224',
            'LMG-2021-134',
            'LMG-2024-915',
            'LMG-2018-054',
            'LMG-2019-079',
            'LMG-2023-623',
            'LMG-2024-1065',
            'LMG-2023-775',
            'LMG-2024-232',
            'LMG-2025-370',
            'LMG-2024-874',
            'LMG-2024-1050',
            'LMG-2024-1039',
            'LMG-2024-277',
            'LMG-2024-309',
            'LMG-2025-297',
            'LMG-2025-699',
        ];

        DB::table('users')
            ->whereIn('nip', $kodeKaryawan)
            ->update([
                'kategori_Gaji' => 'harian',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $kodeKaryawan = [
            // gunakan array yang sama seperti di atas
        ];

        DB::table('users')
            ->whereIn('kode_karyawan', $kodeKaryawan)
            ->update([
                'kategori_Gaji' => null,
            ]);
    }
};