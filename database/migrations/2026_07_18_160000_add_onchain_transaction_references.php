<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('blockchain_tx_hash', 66)->nullable()->after('blockchain_hash');
        });

        Schema::table('vehicle_assignments', function (Blueprint $table) {
            $table->unsignedInteger('pickup_mileage')->nullable()->after('pickup_confirmed_at');
            $table->string('pickup_blockchain_tx_hash', 66)->nullable()->after('pickup_signature_hash');
            $table->string('return_blockchain_tx_hash', 66)->nullable()->after('return_signature_hash');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('content_hash', 64)->nullable()->after('checksum');
            $table->string('blockchain_tx_hash', 66)->nullable()->after('content_hash');
        });

        // Une signature MetaMask ne doit jamais pouvoir authentifier deux comptes.
        DB::table('users')
            ->whereNotNull('wallet_address')
            ->select('wallet_address')
            ->groupBy('wallet_address')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('wallet_address')
            ->each(function (string $wallet): void {
                $ids = DB::table('users')
                    ->where('wallet_address', $wallet)
                    ->orderBy('id')
                    ->pluck('id');

                DB::table('users')
                    ->whereIn('id', $ids->slice(1))
                    ->update(['wallet_address' => null]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('wallet_address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['wallet_address']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['content_hash', 'blockchain_tx_hash']);
        });

        Schema::table('vehicle_assignments', function (Blueprint $table) {
            $table->dropColumn(['pickup_mileage', 'pickup_blockchain_tx_hash', 'return_blockchain_tx_hash']);
        });

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('blockchain_tx_hash');
        });
    }
};
