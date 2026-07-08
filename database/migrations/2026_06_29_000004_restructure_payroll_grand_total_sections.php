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
        Schema::table('payroll_grand_total', function (Blueprint $table) {
            $table->bigInteger('total_lembur')->default(0)->after('detail_harian');
            $table->string('section', 20)->default('cabut')->after('job_label');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payroll_grand_total', function (Blueprint $table) {
            $table->dropColumn(['total_lembur', 'section']);
        });
    }
};
