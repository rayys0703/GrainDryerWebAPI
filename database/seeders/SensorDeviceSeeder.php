<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\BedDryer;
use App\Models\SensorDevice;

class SensorDeviceSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan owner (admin) ada
        $owner = User::where('email', 'rayya@gmail.com')->first() ?? User::first();
        if (!$owner) {
            throw new \RuntimeException('User owner tidak ditemukan. Jalankan UserSeeder dulu.');
        }

        // Pastikan ada bed dryer milik owner
        $dryer = BedDryer::firstOrCreate(
            ['user_id' => $owner->user_id, 'nama' => 'Bed Dryer Utama'],
            ['lokasi' => 'Gudang A', 'deskripsi' => 'Unit utama untuk pengujian']
        );

        // Daftar device per dryer (unik per dryer)
        $devices = [
            [
                'device_id'   => 1,
                'device_name' => 'Tombak 1',
                'address'     => 'iot/sensor/datagabah/1',
                'status'      => true,
            ],
            [
                'device_id'   => 5,
                'device_name' => 'Pembakaran & Pengaduk',
                'address'     => 'iot/sensor/pembakaran/5',
                'status'      => true,
            ],
        ];

        foreach ($devices as $d) {
            SensorDevice::updateOrCreate(
                ['dryer_id' => $dryer->dryer_id, 'device_id' => $d['device_id'], 'device_name' => $d['device_name']],
                ['address' => $d['address'], 'status' => $d['status']]
            );
        }
    }
}
