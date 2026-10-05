<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'kategori_gaji')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('kategori_gaji', 50)->nullable()->after('departemen');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kategori_gaji');
        });
    }
};
