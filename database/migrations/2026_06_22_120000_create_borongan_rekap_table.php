<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBoronganRekapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('borongan_rekap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('borongan_import_id')->constrained('borongan_imports')->onDelete('cascade');
            $table->string('pin', 20)->nullable();
            $table->string('nip', 50);
            $table->string('nama', 100);
            $table->date('periode_dari');
            $table->date('periode_sampai');
            $table->unsignedInteger('total_gram')->default(0);
            $table->unsignedBigInteger('total_upah')->default(0);
            $table->unsignedBigInteger('potongan_bpjs')->default(0);
            $table->unsignedBigInteger('potongan_lain')->default(0);
            $table->unsignedBigInteger('tambahan')->default(0);
            $table->bigInteger('total_akhir')->default(0);
            $table->string('keterangan', 255)->nullable();
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['borongan_import_id', 'nip']);
            $table->index(['nip', 'periode_dari']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('borongan_rekap');
    }
}
