<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE: Direkonstruksi ulang berdasarkan struktur tabel
     * `payroll_details` pada database hris (dummy) per 2026-07-08.
     * Kolom lembur_approved & setengah_hari SENGAJA tidak dimasukkan di
     * sini karena itu ditambahkan (dan lembur_approved sempat dipindah)
     * oleh migration-migration belakangan.
     */
    public function up(): void
    {
        if (!Schema::hasTable('payroll_details')) {
            Schema::create('payroll_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payroll_id');
                $table->string('pin', 20);
                $table->string('nip', 50)->nullable();
                $table->string('nama', 100)->nullable();
                $table->unsignedInteger('nominal_harian')->default(0);
                $table->unsignedInteger('hadir')->default(0);
                $table->unsignedInteger('alpha')->default(0);
                $table->unsignedInteger('izin')->default(0);
                $table->unsignedInteger('sakit')->default(0);
                $table->unsignedInteger('lembur_menit')->default(0);
                $table->bigInteger('gaji_pokok')->default(0);
                $table->bigInteger('gaji_lembur')->default(0);
                $table->bigInteger('tambahan')->default(0);
                $table->bigInteger('potongan')->default(0);
                $table->bigInteger('total_gaji')->default(0);
                $table->text('keterangan')->nullable();
                $table->timestamps();

                // Dump asli hanya punya index gabungan, TIDAK ada FK constraint
                $table->index(['payroll_id', 'pin'], 'payroll_details_payroll_id_pin_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_details');
    }
};
