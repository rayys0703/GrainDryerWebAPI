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
            $table->unsignedInteger('dryer_id');              // refer ke bed dryer tertentu
            $table->unsignedInteger('grain_type_id')->nullable();

            $table->dateTime('timestamp_mulai')->nullable();
            $table->dateTime('timestamp_selesai')->nullable();

            $table->float('berat_gabah_awal')->nullable();
            $table->float('berat_gabah_akhir')->nullable();

            $table->float('kadar_air_awal')->nullable();
            $table->float('kadar_air_target')->nullable();
            $table->float('kadar_air_akhir')->nullable();

            $table->integer('durasi_rekomendasi')->nullable();
            $table->integer('durasi_terlaksana')->default(0);
            $table->float('avg_estimasi_durasi')->nullable();

            $table->enum('status', ['pending', 'ongoing', 'completed'])->default('pending');
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->foreign('dryer_id')->references('dryer_id')->on('bed_dryers')->onDelete('cascade');
            $table->foreign('grain_type_id')->references('grain_type_id')->on('grain_types')->onDelete('set null');

            $table->index(['dryer_id', 'status']);
        });

        Schema::create('prediction_estimations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('process_id');
            $table->float('estimasi_durasi');   // menit
            $table->timestamp('timestamp');
            $table->timestamps();

            $table->foreign('process_id')->references('process_id')->on('drying_process')->onDelete('cascade');

            $table->index(['process_id', 'timestamp']);
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