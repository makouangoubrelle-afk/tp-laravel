<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'super_admin',
                'fleet_manager',
                'driver',
                'mechanic',
                'auditor',
            ])->default('driver')->after('email');
            $table->string('wallet_address')->nullable()->unique()->after('role');
            $table->boolean('is_active')->default(true)->after('wallet_address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'wallet_address', 'is_active']);
        });
    }
};
