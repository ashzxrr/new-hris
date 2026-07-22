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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tl_id')) {
                $table->unsignedBigInteger('tl_id')->nullable()->after('job_level');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tl_id')) {
                return;
            }

            $table->foreign('tl_id')->references('id')->on('users')->onDelete('set null');
            $table->index('tl_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tl_id']);
            $table->dropIndex(['tl_id']);
            $table->dropColumn('tl_id');
        });
    }
};
