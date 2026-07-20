<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE documents MODIFY COLUMN type ENUM('registration', 'insurance', 'invoice', 'inspection', 'contract', 'other') NOT NULL");

        Schema::table('odometer_readings', function (Blueprint $table) {
            $table->boolean('is_locked')->default(true)->after('content_hash');
        });
    }

    public function down(): void
    {
        Schema::table('odometer_readings', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });

        DB::statement("ALTER TABLE documents MODIFY COLUMN type ENUM('registration', 'insurance', 'invoice', 'inspection', 'other') NOT NULL");
    }
};
