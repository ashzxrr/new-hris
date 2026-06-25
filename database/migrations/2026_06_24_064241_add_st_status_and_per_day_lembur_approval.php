<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modify attendance_corrections
        DB::statement("ALTER TABLE attendance_corrections MODIFY status ENUM('H','A','I','S','ST','GL','Cuti','DLL') NOT NULL DEFAULT 'H'");

        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->boolean('lembur_approved')->default(false)->after('lembur_menit');
        });

        // 2. Modify payroll_details
        Schema::table('payroll_details', function (Blueprint $table) {
            $table->dropColumn('lembur_approved');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Restore payroll_details
        Schema::table('payroll_details', function (Blueprint $table) {
            $table->boolean('lembur_approved')->default(false)->after('lembur_menit');
        });

        // 2. Restore attendance_corrections
        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->dropColumn('lembur_approved');
        });

        DB::statement("ALTER TABLE attendance_corrections MODIFY status ENUM('H','A','I','S','GL','Cuti','DLL') NOT NULL DEFAULT 'H'");
    }
};

