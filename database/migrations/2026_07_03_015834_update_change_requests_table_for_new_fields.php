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
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn('sifat_perubahan');
        });
        Schema::table('change_requests', function (Blueprint $table) {
            $table->string('sifat_perubahan')->nullable()->default('Formula');
            $table->text('awal_sebelum_perubahan')->nullable();
            $table->text('usulan_perubahan')->nullable();
            $table->text('alasan_perubahan')->nullable();
            $table->text('analisis_dampak')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('change_requests', function (Blueprint $table) {
            $table->dropColumn([
                'sifat_perubahan',
                'awal_sebelum_perubahan',
                'usulan_perubahan',
                'alasan_perubahan',
                'analisis_dampak'
            ]);
        });
        Schema::table('change_requests', function (Blueprint $table) {
            $table->enum('sifat_perubahan', ['Permanen', 'Sementara'])->default('Permanen');
        });
    }
};
