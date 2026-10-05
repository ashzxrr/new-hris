<?php

namespace Database\Seeders;

use App\Models\PkwtExport;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PkwtCounterSeeder extends Seeder
{
    /**
     * Seed nomor urut awal PKWT = 149 (dummy, tidak akan tampil di riwayat).
     */
    public function run(): void
    {
        // Cek apakah sudah ada nomor urut 149
        $exists = PkwtExport::where('nomor_urut', 149)->exists();

        if (! $exists) {
            PkwtExport::create([
                'user_id'        => 1, // user dummy
                'nomor_urut'     => 149,
                'nomor_surat'    => '149/PKWT/HRGA/VII/2026',
                'tanggal_mulai'  => Carbon::now()->subDay(),
                'tanggal_selesai' => Carbon::now()->subDay(),
                'tanggal_dibuat' => Carbon::now()->subDay(),
                'tempat_dibuat'  => 'Lamongan',
                'dibuat_oleh'    => 1, // auth_user dummy, adjust jika perlu
            ]);
        }
    }
}
