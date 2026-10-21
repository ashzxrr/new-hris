<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * NOTE: Direkonstruksi ulang berdasarkan struktur tabel `auth_users`
     * pada database hris (dummy) per 2026-07-08. Guard hasTable dipasang
     * karena hris_waj sudah punya tabel ini berikut data akunnya.
     */
    public function up(): void
    {
        if (!Schema::hasTable('auth_users')) {
            Schema::create('auth_users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('username');
                $table->string('password');
                $table->enum('role', ['admin', 'hrd', 'payroll', 'ga']);
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_users');
    }
};
