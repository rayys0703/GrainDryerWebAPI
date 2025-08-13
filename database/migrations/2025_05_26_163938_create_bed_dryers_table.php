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
        Schema::create('bed_dryers', function (Blueprint $table) {
            $table->increments('dryer_id');
            $table->unsignedInteger('user_id');
            $table->string('nama', 100);
            $table->string('lokasi', 150)->nullable();
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bed_dryers');
    }
};