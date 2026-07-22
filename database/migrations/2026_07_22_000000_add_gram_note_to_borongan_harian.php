<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borongan_harian', function (Blueprint $table) {
            $table->string('gram_note', 255)->nullable()->after('berat_gram');
        });
    }

    public function down(): void
    {
        Schema::table('borongan_harian', function (Blueprint $table) {
            $table->dropColumn('gram_note');
        });
    }
};
