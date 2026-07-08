<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE karyawan_bank MODIFY nip VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL");
        DB::statement("ALTER TABLE payroll_pengajuan MODIFY nip VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Intentionally left blank: keep utf8mb4_unicode_ci as the canonical collation for nip.
    }
};
