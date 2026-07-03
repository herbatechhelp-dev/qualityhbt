<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE deviations MODIFY COLUMN status ENUM('DRAFT', 'OPEN', 'IN REVIEW', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'OPEN'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE deviations MODIFY COLUMN status ENUM('OPEN', 'IN REVIEW', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'OPEN'");
        }
    }
};
