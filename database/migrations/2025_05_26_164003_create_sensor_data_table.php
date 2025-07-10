<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sensor_data', function (Blueprint $table) {
            $table->increments('sensor_id'); 
            $table->unsignedInteger('process_id'); 
            $table->unsignedInteger('device_id'); 
            $table->dateTime('timestamp')->useCurrent(); 
            $table->float('kadar_air_gabah')->nullable(); 
            $table->float('suhu_gabah')->nullable(); 
            $table->float('suhu_ruangan')->nullable();
            $table->float('suhu_pembakaran')->nullable();
            $table->boolean('status_pengaduk')->nullable()->default(NULL);

            $table->foreign('process_id')->references('process_id')->on('drying_process')->onDelete('cascade');
            $table->foreign('device_id')->references('device_id')->on('sensor_devices')->onDelete('cascade');

            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_data');
    }
};