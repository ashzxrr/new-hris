<?php

namespace Database\Seeders;

use App\Models\BoronganRate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BoronganRateSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $today = now()->toDateString();

        // Cabut rates
        BoronganRate::updateOrCreate(
            ['jenis' => 'cabut', 'kode_kategori' => 'VIP'],
            [
                'nama_kategori'  => 'VIP',
                'rate_per_gram'  => 693,
                'berlaku_dari'   => $today,
            ]
        );

        BoronganRate::updateOrCreate(
            ['jenis' => 'cabut', 'kode_kategori' => 'BS_A'],
            [
                'nama_kategori'  => 'Bulu Super A',
                'rate_per_gram'  => 693,
                'berlaku_dari'   => $today,
            ]
        );

        BoronganRate::updateOrCreate(
            ['jenis' => 'cabut', 'kode_kategori' => 'BS_B'],
            [
                'nama_kategori'  => 'Bulu Super B',
                'rate_per_gram'  => 980,
                'berlaku_dari'   => $today,
            ]
        );

        BoronganRate::updateOrCreate(
            ['jenis' => 'cabut', 'kode_kategori' => 'BS_C'],
            [
                'nama_kategori'  => 'Bulu Super C',
                'rate_per_gram'  => 980,
                'berlaku_dari'   => $today,
            ]
        );

        // Cetak/HCR rates (if not already seeded)
        BoronganRate::updateOrCreate(
            ['jenis' => 'cetak', 'kode_kategori' => 'CETAK_INDOMIE'],
            [
                'nama_kategori'  => 'Cetak / HCR Indomie',
                'rate_per_gram'  => 125,
                'berlaku_dari'   => $today,
            ]
        );
    }
}
