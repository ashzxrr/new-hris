<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('borongan_harian', function (Blueprint $table) {
            $table->unsignedInteger('tambahan_training')->default(0)->after('upah_sistem');
        });
    }

    public function down()
    {
        Schema::table('borongan_harian', function (Blueprint $table) {
            $table->dropColumn('tambahan_training');
        });
    }
};
