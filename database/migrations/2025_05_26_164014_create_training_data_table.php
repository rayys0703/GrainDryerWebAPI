<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::create('training_data', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('training_group_id')->constrained()->onDelete('cascade');
        //     $table->foreignId('device_id')->nullable()->constrained('sensor_devices', 'device_id')->onDelete('set null');
        //     $table->float('grain_temperature')->nullable();
        //     $table->float('grain_moisture')->nullable();
        //     $table->float('room_temperature')->nullable();
        //     $table->float('burning_temperature')->nullable();
        //     $table->boolean('stirrer_status')->nullable()->default(false);
        //     $table->float('weight')->nullable();
        //     $table->timestamps();
        // });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_data');
    }
};