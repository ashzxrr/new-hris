<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('borongan_mutasi_log', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('payroll_id');
            $table->string('nip', 50);

            $table->string('jenis_a', 20);
            $table->unsignedBigInteger('import_id_a');

            $table->string('jenis_b', 20);
            $table->unsignedBigInteger('import_id_b');

            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');

            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['payroll_id', 'nip']);

            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');

            $table->foreign('import_id_a')->references('id')->on('borongan_imports')->onDelete('cascade');
            $table->foreign('import_id_b')->references('id')->on('borongan_imports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('borongan_mutasi_log');
    }
};
