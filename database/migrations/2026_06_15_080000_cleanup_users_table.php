<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE: Berdasarkan nama migration ini dan fakta kolom `nominal_harian`
     * tidak ada lagi di skema final `users`, migration ini diasumsikan
     * menghapus kolom tersebut (digantikan oleh tabel salary_configs).
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'nominal_harian')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('nominal_harian');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('nominal_harian')->nullable()->after('kategori_gaji');
        });
    }
};
