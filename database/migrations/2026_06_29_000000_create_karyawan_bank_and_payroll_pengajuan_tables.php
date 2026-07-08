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
        Schema::create('karyawan_bank', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nip', 50)->unique();
            $table->string('nama_bank', 50)->nullable();
            $table->string('no_rekening', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_pengajuan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payroll_id');
            $table->string('nip', 50);
            $table->string('nama', 100);
            $table->string('jenis', 20);
            $table->bigInteger('gaji_real')->default(0);
            $table->bigInteger('komplain')->default(0);
            $table->bigInteger('insentif')->default(0);
            $table->bigInteger('potongan_lain')->default(0);
            $table->bigInteger('potongan_bpjs')->default(0);
            $table->bigInteger('total_akhir')->default(0);
            $table->string('no_rekening', 50)->nullable();
            $table->string('nama_bank', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->timestamp('diajukan_at')->nullable();
            $table->unsignedBigInteger('diajukan_by')->nullable();
            $table->timestamps();

            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
            $table->unique(['payroll_id', 'nip', 'jenis'], 'unique_nip_jenis_per_payroll');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payroll_pengajuan');
        Schema::dropIfExists('karyawan_bank');
    }
};
