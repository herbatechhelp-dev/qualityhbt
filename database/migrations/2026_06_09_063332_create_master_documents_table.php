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
        Schema::create('master_documents', function (Blueprint $table) {
            $table->id();
            $table->string('excel_no')->nullable(); // Column A
            $table->text('title'); // Column B: JUDUL
            $table->string('document_number')->unique(); // Column C: NO DOKUMEN
            $table->string('revision')->nullable(); // Column D: REVISI PERUBAHAN
            $table->string('no_perubahan_cc')->nullable(); // Column E: NO PERUBAHAN / CC
            $table->date('effective_date')->nullable(); // Column F: TGL BERLAKU
            $table->date('tgl_review')->nullable(); // Column G: TGL REVIEW
            $table->date('tgl_review_2')->nullable(); // Column H: TGL REVIEW
            $table->string('pengganti_lampiran')->nullable(); // Column I: PENGGANTI LAMPIRAN
            $table->string('no_catatan_mutu')->nullable(); // Column J: NO. CATATAN MUTU YANG TERLAMPIR
            $table->text('dokumen_terkait')->nullable(); // Column K: DOKUMEN TERKAIT
            $table->date('tgl_sosialisasi')->nullable(); // Column L: TGL SOSIALISASI
            $table->text('distribusi')->nullable(); // Column M: DISTRIBUSI
            $table->string('no_pemusnahan')->nullable(); // Column N: NO PEMUSNAHAN
            $table->date('tgl_pemusnahan')->nullable(); // Column O: TGL PEMUSNAHAN
            $table->string('tempat_penyimpanan')->nullable(); // Column P: TEMPAT PENYIMPANAN
            
            // Meta categories
            $table->enum('type', ['PROTAP', 'QMS', 'IK', 'SPESIFIKASI', 'CRF_CRE', 'PROTOKOL', 'LAPORAN_TAHUNAN']);
            $table->string('status')->default('Active');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_documents');
    }
};
