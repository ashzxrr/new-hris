<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->time('jam_out')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->time('jam_out')->nullable(false)->change();
        });
    }
};
