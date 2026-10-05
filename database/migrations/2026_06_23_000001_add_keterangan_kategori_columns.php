<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE: Nama migration menyebut "kategori" juga, tapi kolom `kategori`
     * sudah ada sejak migration create_borongan_tables. Berdasarkan skema
     * final, satu-satunya kolom baru di sini adalah `keterangan`
     * (kemungkinan bagian "kategori" di nama migration merujuk ke
     * perubahan lain yang sudah tercakup di migration lain / tidak
     * menghasilkan perubahan skema yang bisa dideteksi dari dump).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('borongan_harian', 'keterangan')) {
            Schema::table('borongan_harian', function (Blueprint $table) {
                $table->string('keterangan', 255)->nullable()->after('nojob');
            });
        }
    }

    public function down(): void
    {
        Schema::table('borongan_harian', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};
