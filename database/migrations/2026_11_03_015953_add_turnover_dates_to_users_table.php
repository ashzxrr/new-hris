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
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tanggal_masuk')) {
                $table->date('tanggal_masuk')->nullable();
            }

            if (! Schema::hasColumn('users', 'tanggal_resign')) {
                $table->date('tanggal_resign')->nullable();
            }

            if (! Schema::hasColumn('users', 'alasan_resign')) {
                $table->string('alasan_resign')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        foreach (['tanggal_masuk', 'tanggal_resign', 'alasan_resign'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
