<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE documents MODIFY COLUMN type ENUM('registration', 'insurance', 'invoice', 'inspection', 'contract', 'other') NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_type_check');
            DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_type_check CHECK (type IN ('registration', 'insurance', 'invoice', 'inspection', 'contract', 'other'))");
        }

        Schema::table('odometer_readings', function (Blueprint $table) {
            if (! Schema::hasColumn('odometer_readings', 'is_locked')) {
                $table->boolean('is_locked')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('odometer_readings', function (Blueprint $table) {
            if (Schema::hasColumn('odometer_readings', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE documents MODIFY COLUMN type ENUM('registration', 'insurance', 'invoice', 'inspection', 'other') NOT NULL");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE documents DROP CONSTRAINT IF EXISTS documents_type_check');
            DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_type_check CHECK (type IN ('registration', 'insurance', 'invoice', 'inspection', 'other'))");
        }
    }
};
