<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalaryConfigsTable extends Migration
{
    public function up()
    {
        Schema::create('salary_configs', function (Blueprint $table) {
            $table->id();
            $table->string('pin', 20);
            $table->string('nip', 50)->nullable();
            $table->string('nama', 100)->nullable();
            $table->enum('kategori_gaji', ['harian', 'borongan', 'bulanan']);
            $table->unsignedBigInteger('nominal')->default(0);
            $table->date('berlaku_dari');
            $table->string('keterangan', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['pin', 'berlaku_dari']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('salary_configs');
    }
}
