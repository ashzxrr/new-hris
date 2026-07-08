<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KaryawanBank;

class KaryawanBankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $path = database_path('seeders/data/karyawan_bank_seed.csv');

        if (!file_exists($path)) {
            $this->command->error("Seed file not found: {$path}");
            return;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->command->error("Unable to open seed file: {$path}");
            return;
        }

        $row = 0;
        $processed = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $row++;

            // Skip header row
            if ($row === 1) {
                continue;
            }

            // Expected columns: nip, no_rekening, nama_bank, email
            $nip = isset($data[0]) ? trim($data[0]) : null;
            if (! $nip) {
                continue;
            }

            $no_rekening = isset($data[1]) ? trim($data[1]) : null;
            $nama_bank = isset($data[2]) ? trim($data[2]) : null;
            $email = isset($data[3]) ? trim($data[3]) : null;

            KaryawanBank::updateOrCreate([
                'nip' => $nip,
            ], [
                'no_rekening' => $no_rekening ?: null,
                'nama_bank' => $nama_bank ?: null,
                'email' => $email ?: null,
            ]);

            $processed++;
        }

        fclose($handle);

        $this->command->info("Processed {$processed} rows from {$path}.");
    }
}
