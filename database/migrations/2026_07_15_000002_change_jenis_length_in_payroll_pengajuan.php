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
            // widen jenis to varchar(100) to allow longer job labels
            $table->string('jenis', 100)->change();
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
            $table->string('jenis', 20)->change();
        });
    }
};
