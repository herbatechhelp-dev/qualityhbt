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
            $table->text('fishbone_machine')->nullable()->after('risk_analysis');
            $table->text('fishbone_man')->nullable()->after('fishbone_machine');
            $table->text('fishbone_method')->nullable()->after('fishbone_man');
            $table->text('fishbone_milieu')->nullable()->after('fishbone_method');
            $table->text('fishbone_measurement')->nullable()->after('fishbone_milieu');
            $table->text('fishbone_materials')->nullable()->after('fishbone_measurement');
            $table->text('root_cause')->nullable()->after('fishbone_materials');
            $table->text('risk_identification_details')->nullable()->after('root_cause');
            $table->text('risk_analysis_details')->nullable()->after('risk_identification_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deviations', function (Blueprint $table) {
            $table->dropColumn([
                'fishbone_machine',
                'fishbone_man',
                'fishbone_method',
                'fishbone_milieu',
                'fishbone_measurement',
                'fishbone_materials',
                'root_cause',
                'risk_identification_details',
                'risk_analysis_details',
            ]);
        });
    }
};
