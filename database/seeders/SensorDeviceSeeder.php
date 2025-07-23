<?php

namespace Database\Seeders;

use App\Models\SensorDevice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SensorDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $sensors = [
            [
                'device_id' => 1,
                'device_name' => 'Tombak 1',
                'address' => 'iot/sensor/datagabah/1',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // [
            //     'device_id' => 2,
            //     'device_name' => 'Sensor Tombak 2',
            //     'address' => 'iot/sensor/datagabah/2',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'device_id' => 3,
            //     'device_name' => 'Sensor Tombak 3',
            //     'address' => 'iot/sensor/datagabah/3',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'device_id' => 4,
            //     'device_name' => 'Sensor Tombak 4',
            //     'address' => 'iot/sensor/datagabah/4',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            [
                'device_id' => 5,
                'device_name' => 'Pembakaran & Pengaduk',
                'address' => 'iot/sensor/pembakaran/5',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($sensors as $sensor) {
            // Cek apakah device_id sudah ada untuk menghindari duplikasi
            if (!SensorDevice::where('device_id', $sensor['device_id'])->exists()) {
                SensorDevice::create($sensor);
            }
        }
    }
}