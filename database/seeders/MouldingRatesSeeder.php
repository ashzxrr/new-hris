<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MouldingRatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $today = now()->format('Y-m-d');

        DB::table('borongan_rates')->insert([
            [
                'kode_kategori' => 'NAT_SBG',
                'nama_kategori' => 'NAT SBG',
                'jenis' => 'moulding',
                'rate_per_gram' => 320,
                'berlaku_dari' => $today,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_kategori' => 'SBG',
                'nama_kategori' => 'SBG',
                'jenis' => 'moulding',
                'rate_per_gram' => 320,
                'berlaku_dari' => $today,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_kategori' => 'SBG_WAJ',
                'nama_kategori' => 'SBG WAJ',
                'jenis' => 'moulding',
                'rate_per_gram' => 320,
                'berlaku_dari' => $today,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_kategori' => 'NAT',
                'nama_kategori' => 'NAT',
                'jenis' => 'moulding',
                'rate_per_gram' => 190,
                'berlaku_dari' => $today,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_kategori' => 'VIP_WAJ',
                'nama_kategori' => 'VIP WAJ',
                'jenis' => 'moulding',
                'rate_per_gram' => 190,
                'berlaku_dari' => $today,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_kategori' => 'PT',
                'nama_kategori' => 'PT',
                'jenis' => 'moulding',
                'rate_per_gram' => 150,
                'berlaku_dari' => $today,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_kategori' => 'GPU_NORMAL',
                'nama_kategori' => 'GPU Normal',
                'jenis' => 'moulding',
                'rate_per_gram' => 140,
                'berlaku_dari' => $today,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_kategori' => 'GPU_RENDAMAN',
                'nama_kategori' => 'GPU Rendaman',
                'jenis' => 'moulding',
                'rate_per_gram' => 320,
                'berlaku_dari' => $today,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
