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
            if (! Schema::hasColumn('users', 'alamat')) {
                $table->text('alamat')->nullable()->after('nik');
            }
            if (! Schema::hasColumn('users', 'no_hp')) {
                $table->string('no_hp', 20)->nullable()->after('alamat');
            }
            if (! Schema::hasColumn('users', 'tempat_lahir')) {
                $table->string('tempat_lahir')->nullable()->after('no_hp');
            }
            if (! Schema::hasColumn('users', 'tanggal_lahir')) {
                $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            }
        });

        // Ubah nik menjadi varchar(20) nullable jika belum
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['alamat', 'no_hp', 'tempat_lahir', 'tanggal_lahir']);
        });
    }
};
