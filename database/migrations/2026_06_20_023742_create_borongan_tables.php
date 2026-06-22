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
        Schema::create('borongan_rates', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kategori', 50); // VIP, BS_A, BS_B, HCR_INDOMIE, CETAK_INDOMIE, dst
            $table->string('nama_kategori', 100);
            $table->string('jenis', 20); // cabut, cetak, moulding
            $table->unsignedInteger('rate_per_gram');
            $table->date('berlaku_dari');
            $table->timestamps();
            $table->index(['jenis', 'kode_kategori']);
        });

        Schema::create('borongan_imports', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 20); // cabut, cetak, moulding
            $table->string('filename', 255);
            $table->date('tanggal_dari');
            $table->date('tanggal_sampai');
            $table->unsignedInteger('total_baris')->default(0);
            $table->unsignedInteger('total_flagged')->default(0);
            $table->enum('status', ['pending', 'reviewed', 'approved'])->default('pending');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });

        Schema::create('borongan_harian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borongan_import_id')->constrained('borongan_imports')->onDelete('cascade');
            $table->string('pin', 20)->nullable();
            $table->string('nip', 50)->nullable();
            $table->string('nama', 100)->nullable();
            $table->date('tanggal');
            $table->string('kategori', 50);
            $table->unsignedInteger('berat_gram')->default(0);
            $table->unsignedInteger('upah_sistem')->default(0);
            $table->unsignedInteger('upah_file')->default(0);
            $table->integer('selisih')->default(0);
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason', 255)->nullable(); // 'NIP tidak ditemukan', 'Selisih upah'
            $table->enum('status', ['pending', 'approved'])->default('pending');
            $table->timestamps();
            $table->index(['pin', 'tanggal']);
            $table->index(['borongan_import_id', 'is_flagged']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borongan_harian');
        Schema::dropIfExists('borongan_imports');
        Schema::dropIfExists('borongan_rates');
    }
};
