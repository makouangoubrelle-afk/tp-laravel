<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_assignments', function (Blueprint $table) {
            $table->timestamp('pickup_confirmed_at')->nullable()->after('assigned_at');
            $table->string('pickup_signature_hash')->nullable()->after('pickup_confirmed_at');
            $table->string('return_signature_hash')->nullable()->after('end_mileage');
            $table->string('content_hash')->nullable()->after('blockchain_tx_hash');
            $table->text('mission_notes')->nullable()->after('return_signature_hash');
            $table->enum('status', ['pending_pickup', 'active', 'completed'])->default('pending_pickup')->after('mission_notes');
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium')->after('type');
        });

        \Illuminate\Support\Facades\DB::table('vehicle_assignments')
            ->whereNotNull('returned_at')
            ->update(['status' => 'completed']);

        \Illuminate\Support\Facades\DB::table('vehicle_assignments')
            ->whereNull('returned_at')
            ->update(['status' => 'active', 'pickup_confirmed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('vehicle_assignments', function (Blueprint $table) {
            $table->dropColumn([
                'pickup_confirmed_at', 'pickup_signature_hash', 'return_signature_hash',
                'content_hash', 'mission_notes', 'status',
            ]);
        });

        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
