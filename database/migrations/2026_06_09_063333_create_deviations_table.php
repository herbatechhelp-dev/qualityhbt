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
        Schema::create('deviations', function (Blueprint $table) {
            $table->id();
            $table->string('deviation_number')->unique();
            $table->string('department')->default('Quality Assurance');
            $table->text('description');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_description')->nullable();
            $table->enum('status', ['OPEN', 'IN REVIEW', 'APPROVED', 'REJECTED'])->default('OPEN');
            $table->foreignId('initiator_id')->constrained('users')->onDelete('cascade');
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deviations');
    }
};
