<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seed minimal grain type
        DB::table('grain_types')->updateOrInsert(
            ['nama_jenis' => 'Ciherang'],
            ['deskripsi' => '...', 'created_at' => now(), 'updated_at' => now()]
        );

        $this->call([
            UserSeeder::class,
            SensorDeviceSeeder::class,      // ini juga memastikan ada bed dryer default
            TrainingFileExcelSeeder::class, // impor file .xlsx -> proses + sensor_data + prediction + datasets
        ]);
    }
}
