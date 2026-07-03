<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deviations', function (Blueprint $table) {
            $table->string('pic')->nullable()->after('department');
            $table->date('tanggal_temuan')->nullable()->after('pic');
            $table->json('jenis_penyimpangan')->nullable()->after('description'); // array of selected categories
            $table->json('identifikasi_penyimpangan')->nullable()->after('jenis_penyimpangan'); // array of selected identifications
            $table->string('kepala_departemen')->nullable()->after('identifikasi_penyimpangan'); // selected user name
            $table->json('attachments')->nullable()->after('attachment_description'); // [{path, description}]
            $table->json('risk_analysis')->nullable()->after('attachments'); // [{risk_id, cause, s, o, d, rpn, control, action}]
        });
    }

    public function down(): void
    {
        Schema::table('deviations', function (Blueprint $table) {
            $table->dropColumn([
                'pic', 'tanggal_temuan', 'jenis_penyimpangan',
                'identifikasi_penyimpangan', 'kepala_departemen',
                'attachments', 'risk_analysis',
            ]);
        });
    }
};
