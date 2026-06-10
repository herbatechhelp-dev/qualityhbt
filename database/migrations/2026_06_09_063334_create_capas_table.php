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
        Schema::create('capas', function (Blueprint $table) {
            $table->id();
            $table->string('capa_number')->unique();
            $table->foreignId('deviation_id')->constrained('deviations')->onDelete('cascade');
            $table->string('deviation_number_ref');
            $table->date('tanggal_penyimpangan');
            $table->string('type_capa')->default('Deviasi');
            $table->text('tindakan_capa')->nullable();
            $table->foreignId('pic_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('bukti_lapangan_path')->nullable();
            $table->text('hasil_verifikasi_qa')->nullable();
            $table->enum('status', ['DRAFT', 'IN PROGRESS', 'APPROVED', 'CLOSE'])->default('DRAFT');
            $table->foreignId('initiator_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capas');
    }
};
