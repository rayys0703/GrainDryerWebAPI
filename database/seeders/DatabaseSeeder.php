<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed for grain_types table
        DB::table('grain_types')->insert([
            [
                'nama_jenis' => 'Ciherang',
                'deskripsi' => '...',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);

        $this->call([
            UserSeeder::class,
            SensorDeviceSeeder::class,
            TrainingDataSeeder::class,
            // Anda bisa menambahkan seeder lain di sini di masa mendatang
            // GrainTypeSeeder::class,
            // SensorDeviceSeeder::class,
        ]);
    }
}