<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE: Kolom `nominal_harian` ini nantinya DIHAPUS lagi oleh migration
     * 2026_06_15_080000_cleanup_users_table (digantikan oleh tabel
     * salary_configs). Ditulis ulang di sini hanya demi menjaga urutan
     * riwayat migration tetap konsisten dengan tabel `migrations`.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'nominal_harian')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('nominal_harian')->nullable()->after('kategori_gaji');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nominal_harian');
        });
    }
};
