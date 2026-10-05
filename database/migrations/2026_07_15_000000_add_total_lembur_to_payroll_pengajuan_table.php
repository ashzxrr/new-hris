<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payroll_pengajuan', function (Blueprint $table) {
            $table->integer('total_lembur')->default(0)->after('potongan_bpjs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_pengajuan', function (Blueprint $table) {
            $table->dropColumn('total_lembur');
        });
    }
};
