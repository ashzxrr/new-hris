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
        Schema::create('payroll_grand_total', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_id');
            $table->string('nip', 50);
            $table->string('nama', 100);
            $table->string('job_label', 100)->nullable();
            $table->json('detail_harian');
            $table->unsignedBigInteger('insentif')->default(0);
            $table->unsignedBigInteger('komplain')->default(0);
            $table->unsignedBigInteger('potongan_lain')->default(0);
            $table->unsignedBigInteger('potongan_bpjs')->default(0);
            $table->bigInteger('total_akhir')->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            
            $table->foreign('payroll_id')
                ->references('id')
                ->on('payrolls')
                ->onDelete('cascade');
            
            $table->unique(['payroll_id', 'nip'], 'unique_nip_per_payroll');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_grand_total');
    }
};
