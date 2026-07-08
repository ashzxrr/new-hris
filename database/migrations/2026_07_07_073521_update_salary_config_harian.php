<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $data = [
            ['nip' => 'LMG-2025-363', 'nominal' => 75000],
            ['nip' => 'LMG-2025-617', 'nominal' => 70000],
            ['nip' => 'LMG-2015-008', 'nominal' => 85000],
            ['nip' => 'LMG-2025-213', 'nominal' => 80000],
            ['nip' => 'LMG-2025-472', 'nominal' => 70000],
            ['nip' => 'LMG-2025-282', 'nominal' => 80000],
            ['nip' => 'LMG-2025-638', 'nominal' => 70000],
            ['nip' => 'LMG-2025-714', 'nominal' => 65000],
            ['nip' => 'LMG-2025-347', 'nominal' => 75000],
            ['nip' => 'LMG-2025-623', 'nominal' => 70000],
            ['nip' => 'LMG-2025-361', 'nominal' => 75000],
            ['nip' => 'LMG-2025-165', 'nominal' => 90000],
            ['nip' => 'LMG-2025-306', 'nominal' => 80000],
            ['nip' => 'LMG-2025-499', 'nominal' => 70000],
            ['nip' => 'LMG-2025-518', 'nominal' => 70000],
            ['nip' => 'LMG-2025-303', 'nominal' => 80000],
            ['nip' => 'LMG-2025-304', 'nominal' => 85000],
            ['nip' => 'LMG-2025-662', 'nominal' => 70000],
            ['nip' => 'LMG-2024-153', 'nominal' => 95000],
            ['nip' => 'LMG-2025-136', 'nominal' => 90000],
            ['nip' => 'LMG-2025-675', 'nominal' => 65000],
            ['nip' => 'LMG-2025-169', 'nominal' => 80000],
            ['nip' => 'LMG-2025-157', 'nominal' => 80000],
            ['nip' => 'LMG-2025-446', 'nominal' => 70000],
            ['nip' => 'LMG-2025-520', 'nominal' => 70000],
            ['nip' => 'LMG-2025-208', 'nominal' => 80000],
            ['nip' => 'LMG-2025-285', 'nominal' => 80000],
            ['nip' => 'LMG-2025-515', 'nominal' => 70000],
            ['nip' => 'LMG-2025-357', 'nominal' => 75000],
            ['nip' => 'LMG-2025-393', 'nominal' => 70000],
            ['nip' => 'LMG-2025-450', 'nominal' => 70000],
            ['nip' => 'LMG-2025-451', 'nominal' => 70000],
            ['nip' => 'LMG-2025-457', 'nominal' => 70000],
            ['nip' => 'LMG-2025-366', 'nominal' => 75000],
            ['nip' => 'LMG-2025-542', 'nominal' => 70000],
            ['nip' => 'LMG-2025-579', 'nominal' => 70000],
            ['nip' => 'LMG-2025-192', 'nominal' => 80000],
            ['nip' => 'LMG-2025-519', 'nominal' => 70000],
            ['nip' => 'LMG-2025-476', 'nominal' => 70000],
            ['nip' => 'LMG-2025-582', 'nominal' => 70000],
            ['nip' => 'LMG-2025-489', 'nominal' => 70000],
            ['nip' => 'LMG-2025-521', 'nominal' => 70000],
            ['nip' => 'LMG-2025-475', 'nominal' => 70000],
            ['nip' => 'LMG-2025-492', 'nominal' => 70000],
            ['nip' => 'LMG-2025-549', 'nominal' => 70000],
            ['nip' => 'LMG-2025-209', 'nominal' => 80000],
            ['nip' => 'LMG-2025-210', 'nominal' => 80000],
            ['nip' => 'LMG-2025-284', 'nominal' => 80000],
            ['nip' => 'LMG-2025-358', 'nominal' => 75000],
            ['nip' => 'LMG-2025-362', 'nominal' => 75000],
            ['nip' => 'LMG-2025-444', 'nominal' => 70000],
            ['nip' => 'LMG-2025-455', 'nominal' => 70000],
            ['nip' => 'LMG-2025-495', 'nominal' => 70000],
            ['nip' => 'LMG-2025-553', 'nominal' => 70000],
            ['nip' => 'LMG-2025-630', 'nominal' => 70000],
            ['nip' => 'LMG-2025-156', 'nominal' => 80000],
            ['nip' => 'LMG-2025-541', 'nominal' => 70000],
            ['nip' => 'LMG-2025-524', 'nominal' => 70000],
            ['nip' => 'LMG-2025-500', 'nominal' => 70000],
            ['nip' => 'LMG-2025-494', 'nominal' => 70000],
            ['nip' => 'LMG-2025-626', 'nominal' => 70000],
            ['nip' => 'LMG-2025-459', 'nominal' => 70000],
            ['nip' => 'LMG-2025-543', 'nominal' => 70000],
            ['nip' => 'LMG-2025-556', 'nominal' => 70000],
            ['nip' => 'LMG-2025-629', 'nominal' => 70000],
            ['nip' => 'LMG-2025-458', 'nominal' => 70000],
            ['nip' => 'LMG-2025-478', 'nominal' => 70000],
            ['nip' => 'LMG-2025-490', 'nominal' => 70000],
            ['nip' => 'LMG-2025-497', 'nominal' => 70000],
            ['nip' => 'LMG-2025-525', 'nominal' => 70000],
            ['nip' => 'LMG-2025-370', 'nominal' => 75000],
            ['nip' => 'LMG-2025-297', 'nominal' => 75000],
            ['nip' => 'LMG-2025-699', 'nominal' => 65000],
        ];

        foreach ($data as $item) {
            DB::table('salary_configs')
                ->where('nip', $item['nip'])
                ->update([
                    'nominal' => $item['nominal'],
                ]);
        }
    }

    public function down(): void
    {
        //
    }
};