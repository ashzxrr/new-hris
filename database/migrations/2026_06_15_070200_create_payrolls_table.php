<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE: Direkonstruksi ulang berdasarkan struktur tabel `payrolls`
     * pada database hris (dummy) per 2026-07-08.
     */
    public function up(): void
    {
        if (!Schema::hasTable('payrolls')) {
            Schema::create('payrolls', function (Blueprint $table) {
                $table->id();
                $table->string('periode', 20)->unique();
                $table->date('tanggal_dari');
                $table->date('tanggal_sampai');
                $table->enum('status', ['draft', 'final'])->default('draft');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
