<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('availability_status', ['available', 'occupied', 'off_duty'])
                ->default('available')
                ->after('is_active');
            $table->string('availability_note', 255)->nullable()->after('availability_status');
            $table->timestamp('availability_updated_at')->nullable()->after('availability_note');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'availability_status',
                'availability_note',
                'availability_updated_at',
            ]);
        });
    }
};
