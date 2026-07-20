<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->string('plate_number')->unique();
            $table->enum('status', ['available', 'on_mission', 'in_repair'])->default('available');
            $table->unsignedInteger('current_mileage')->default(0);
            $table->date('technical_inspection_due')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->date('next_oil_change')->nullable();
            $table->string('blockchain_hash')->nullable();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
