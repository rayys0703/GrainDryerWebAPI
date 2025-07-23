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
        Schema::create('drying_process', function (Blueprint $table) {
            $table->increments('process_id');
            $table->string('lokasi', 100)->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('grain_type_id')->nullable();
            $table->dateTime('timestamp_mulai')->nullable();
            $table->dateTime('timestamp_selesai')->nullable();
            $table->float('berat_gabah_awal')->nullable();
            $table->float('berat_gabah_akhir')->nullable();
            $table->float('kadar_air_awal')->nullable();
            $table->float('kadar_air_target')->nullable();
            $table->float('kadar_air_akhir')->nullable();
            $table->integer('durasi_rekomendasi')->nullable();
            $table->integer('durasi_aktual')->nullable();
            $table->integer('durasi_terlaksana')->default(0);
            $table->float('avg_estimasi_durasi')->nullable();
            $table->enum('status', ['pending', 'ongoing', 'completed'])->default('pending');
            $table->text('catatan')->nullable();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('grain_type_id')->references('grain_type_id')->on('grain_types')->onDelete('cascade');

            $table->timestamps();
        });

        Schema::create('prediction_estimations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('process_id');
            $table->float('estimasi_durasi');
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->foreign('process_id')->references('process_id')->on('drying_process')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drying_process');
    }
};