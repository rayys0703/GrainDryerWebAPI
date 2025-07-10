<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Schema::create('training_groups', function (Blueprint $table) {
        //     $table->id();
        //     $table->foreignId('process_id')->nullable()->constrained('drying_process', 'process_id')->onDelete('cascade');
        //     $table->unsignedInteger('grain_type_id');
        //     $table->integer('drying_time')->unsigned();
        //     $table->timestamps();

        //     $table->foreign('grain_type_id')->references('grain_type_id')->on('grain_types')->onDelete('cascade');
        // });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_groups');
    }
};