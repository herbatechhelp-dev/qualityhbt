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
        Schema::table('deviations', function (Blueprint $table) {
            $table->boolean('is_other_batch_affected')->default(false)->after('identifikasi_penyimpangan');
            $table->text('other_batch_affected_details')->nullable()->after('is_other_batch_affected');
            $table->string('deviation_frequency')->nullable()->after('other_batch_affected_details');
            $table->boolean('is_production_stopped')->default(false)->after('deviation_frequency');
            $table->text('immediate_action_details')->nullable()->after('is_production_stopped');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deviations', function (Blueprint $table) {
            $table->dropColumn([
                'is_other_batch_affected',
                'other_batch_affected_details',
                'deviation_frequency',
                'is_production_stopped',
                'immediate_action_details',
            ]);
        });
    }
};
