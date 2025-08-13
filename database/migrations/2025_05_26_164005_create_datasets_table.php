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
        Schema::create('datasets', function (Blueprint $table) {
            $table->increments('dataset_id');
            $table->unsignedInteger('group_id')->nullable();

            $table->dateTime('timestamp')->nullable();

            $table->decimal('kadar_air_gabah', 10, 7)->nullable();
            $table->decimal('suhu_gabah', 10, 7)->nullable();
            $table->decimal('suhu_ruangan', 10, 7)->nullable();
            $table->decimal('suhu_pembakaran', 10, 7)->nullable();
            $table->boolean('status_pengaduk')->default(false);

            $table->decimal('durasi_aktual', 20, 7)->nullable();           // durasi aktual (menit)

            $table->timestamps();

            $table->foreign('group_id')->references('group_id')->on('datasets_group')->onDelete('set null');

            $table->index(['timestamp']);
            $table->index(['group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datasets');
    }
};