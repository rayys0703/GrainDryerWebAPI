<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('grain_types')->updateOrInsert(
            ['nama_jenis' => 'IR64'],
            ['deskripsi' => '...', 'created_at' => now(), 'updated_at' => now()]
        );

        $this->call([
            UserSeeder::class,
            SensorDeviceSeeder::class, 
            TrainingFileExcelSeeder::class, 
        ]);
    }
}
