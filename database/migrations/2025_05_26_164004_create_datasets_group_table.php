<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Buat tabel datasets_group
        Schema::create('datasets_group', function (Blueprint $table) {
            $table->increments('group_id');

            $table->unsignedInteger('grain_type_id')->nullable();
            $table->decimal('kadar_air_awal', 10, 7)->nullable();
            $table->decimal('kadar_air_akhir', 10, 7)->nullable();
            $table->float('target_kadar_air')->nullable();
            $table->float('massa_awal')->nullable();   
            $table->float('massa_akhir')->nullable();      
            $table->decimal('durasi_aktual', 20, 7)->nullable();  

            $table->timestamps();

            $table->foreign('grain_type_id')->references('grain_type_id')->on('grain_types')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('datasets_group');
    }
};
