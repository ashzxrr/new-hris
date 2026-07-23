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
        Schema::table('borongan_imports', function (Blueprint $table) {
            $table->unsignedInteger('tambahan_gram')->default(0)->after('total_flagged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('borongan_imports', function (Blueprint $table) {
            $table->dropColumn('tambahan_gram');
        });
    }
};
