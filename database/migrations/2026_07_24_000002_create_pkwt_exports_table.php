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
        Schema::create('pkwt_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedInteger('nomor_urut');
            $table->string('nomor_surat');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->date('tanggal_dibuat');
            $table->string('tempat_dibuat', 100)->default('Lamongan');
            $table->foreignId('dibuat_oleh')->constrained('auth_users')->onDelete('cascade');
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pkwt_exports');
    }
};
