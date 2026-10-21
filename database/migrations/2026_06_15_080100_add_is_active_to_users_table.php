<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                // Catatan urutan: salary_config_id BELUM ada di titik ini
                // (baru ditambahkan oleh migration 2026_06_16_000001),
                // jadi is_active diletakkan setelah kategori_gaji dulu.
                $table->boolean('is_active')->default(true)->after('kategori_gaji');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
