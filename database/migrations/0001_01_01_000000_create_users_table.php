<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: Direkonstruksi ulang (file asli hilang) berdasarkan struktur
     * tabel `users`, `password_reset_tokens`, dan `sessions` pada database
     * hris (dummy) per 2026-07-08. Kolom kategori_gaji, salary_config_id,
     * dan is_active TIDAK dimasukkan di sini karena ditambahkan oleh
     * migration terpisah belakangan (lihat 2026_06_15_* dan 2026_06_16_*).
     */
    public function up(): void
    {
        // Guard: di hris_waj tabel `users` SUDAH ADA (data karyawan asli),
        // jadi jangan sampai di-create ulang / ketimpa.
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('pin', 20)->unique();
                $table->string('nip', 50)->nullable();
                $table->string('nama', 100);
                $table->string('nik', 50)->nullable();
                $table->enum('jk', ['L', 'P']);
                $table->string('job_title', 100)->nullable();
                $table->string('job_level', 100)->nullable();
                $table->unsignedBigInteger('tl_id')->nullable();
                $table->string('bagian', 100)->nullable();
                $table->string('departemen', 100)->nullable();
                $table->timestamps();

                $table->index('pin', 'idx_pin');
                $table->index('nama', 'idx_nama');
            });
        }

        if (!Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
