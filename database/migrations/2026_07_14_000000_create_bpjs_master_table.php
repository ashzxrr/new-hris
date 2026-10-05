<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('bpjs_master', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->index();
            $table->unsignedBigInteger('nominal')->default(0);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bpjs_master');
    }
};
