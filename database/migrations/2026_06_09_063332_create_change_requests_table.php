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
        Schema::create('change_requests', function (Blueprint $table) {
            $table->id();
            $table->string('cr_number')->unique();
            $table->string('department')->default('Quality Assurance');
            $table->enum('type', ['CRA', 'CRB']);
            $table->enum('sifat_perubahan', ['Permanen', 'Sementara']);
            $table->text('risk_identification')->nullable();
            $table->text('potential_cause')->nullable();
            $table->integer('severity')->nullable();
            $table->integer('occurrence')->nullable();
            $table->integer('detection')->nullable();
            $table->integer('rpn')->nullable();
            $table->text('risk_control')->nullable();
            $table->text('action')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_description')->nullable();
            $table->enum('status', ['DRAFT', 'OPEN', 'IN REVIEW', 'APPROVED', 'IN PROGRESS', 'COMPLETE', 'REJECT'])->default('DRAFT');
            
            $table->foreignId('initiator_id')->constrained('users')->onDelete('cascade');
            
            // QA Inputs (updated later)
            $table->text('rencana_tindakan')->nullable();
            $table->foreignId('pic_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('timeline')->nullable();
            $table->text('hasil_verifikasi')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
