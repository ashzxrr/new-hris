<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE: Direkonstruksi ulang berdasarkan struktur tabel
     * `attendance_corrections` pada database hris (dummy) per 2026-07-08.
     * Ditulis sesuai kondisi AWAL (sebelum kolom lembur_approved &
     * status 'ST' ditambahkan oleh migration 2026_06_24_064241).
     */
    public function up(): void
    {
        if (!Schema::hasTable('attendance_corrections')) {
            Schema::create('attendance_corrections', function (Blueprint $table) {
                $table->id();
                $table->string('pin', 20);
                $table->date('tanggal');
                $table->time('jam_in')->nullable();
                $table->time('jam_out')->nullable();
                $table->integer('lembur_menit')->nullable();
                $table->enum('status', ['H', 'A', 'I', 'S', 'GL', 'Cuti', 'DLL'])->default('H');
                $table->string('keterangan', 255)->nullable();
                $table->unsignedBigInteger('edited_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_corrections');
    }
};
