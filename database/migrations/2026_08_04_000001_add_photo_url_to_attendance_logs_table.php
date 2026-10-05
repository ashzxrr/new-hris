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
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('attendance_logs', 'photo_url')) {
                $table->string('photo_url', 500)->nullable()->after('machine_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_logs', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_logs', 'photo_url')) {
                $table->dropColumn('photo_url');
            }
        });
    }
};
