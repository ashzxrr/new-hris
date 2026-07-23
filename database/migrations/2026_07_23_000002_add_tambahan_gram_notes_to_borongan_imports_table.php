<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borongan_imports', function (Blueprint $table) {
            $table->text('tambahan_gram_notes')->nullable()->after('tambahan_gram');
        });
    }

    public function down(): void
    {
        Schema::table('borongan_imports', function (Blueprint $table) {
            $table->dropColumn('tambahan_gram_notes');
        });
    }
};
