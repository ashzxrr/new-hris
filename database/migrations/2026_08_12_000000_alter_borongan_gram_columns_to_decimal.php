<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('borongan_harian', function (Blueprint $table) {
            $table->decimal('berat_gram', 12, 2)->unsigned()->change();
        });

        Schema::table('borongan_rekap', function (Blueprint $table) {
            $table->decimal('total_gram', 12, 2)->unsigned()->change();
        });

        Schema::table('borongan_imports', function (Blueprint $table) {
            $table->decimal('tambahan_gram', 12, 2)->unsigned()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borongan_harian', function (Blueprint $table) {
            $table->unsignedInteger('berat_gram')->default(0)->change();
        });

        Schema::table('borongan_rekap', function (Blueprint $table) {
            $table->unsignedInteger('total_gram')->default(0)->change();
        });

        Schema::table('borongan_imports', function (Blueprint $table) {
            $table->unsignedInteger('tambahan_gram')->default(0)->change();
        });
    }
};
