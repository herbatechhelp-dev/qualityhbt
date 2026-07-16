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
            $table->json('evaluasi_tindakan')->nullable()->after('risk_analysis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deviations', function (Blueprint $table) {
            $table->dropColumn('evaluasi_tindakan');
        });
    }
};
